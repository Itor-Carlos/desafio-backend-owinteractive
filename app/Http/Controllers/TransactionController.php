<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\error;

class TransactionController extends Controller
{

    public function store(Request $request){
        $validationTransaction = Validator::make($request->all(), [
            'value' => 'required|numeric|min:0.01',
            'description' => 'string|max:64',
            'type' => 'required|in:debit,credit,refund',
            'notes' => 'string|max:50',
            'currency' => 'required|in:USD,BRL',
            'user_id' => 'required|integer|exists:users,id'
        ], [
            "value.required" => "O campo 'value' é obrigatório",
            "value.numeric" => "O campo 'value' deve ser um número",
            "value.min" => "O campo 'value' deve ter valor mínimo de 0.01",
            "type.in" => "O campo 'type' deve ser um dos seguintes valores: debit, credit ou refund.",
            "type.required" => "O campo 'type' é obrigatório",
            "currency.in" => "O campo 'currency' deve ser USD ou BRL.",
            "currency.required" => "O campo 'currency' é obrigatório",
            "notes.string" => "O campo 'notes' deve ser do tipo string",
            "description.string" => "O campo 'description' deve ser do tipo string",
            "description.max" => "O campo 'description' deve ter no máximo 64 caracteres",
            "notes.max" => "O campo 'notes' deve ter no máximo 50 caracteres",
            "user_id.required" => "O campo 'user_id' é obrigatório",
            "user_id.integer" => "O campo 'user_id' deve ser um número inteiro",
            "user_id.exists" => "O campo 'user_id' deve corresponder a um usuário válido",
        ]);


        if ($validationTransaction->fails()) {
            $erros = $validationTransaction->errors();
            $errosFormatados = [];

            foreach ($erros->toArray() as $campo => $mensagens) {
                $errosFormatados[$campo] = implode(', ', $mensagens);
            }

            return response()->json($errosFormatados,400);
        }
        $createdTransaction = Transaction::create($request->all());

        return response()->json($createdTransaction, '201');
    }

    public function getTransactions(Request $request)
    {
        if(!$request->id) return response()->json([
            "error" => "Informe o id do usuário"
        ], 400);

        $userExists = User::find($request->id);

        if(!$userExists) return response()->json([
            "error" => "Não existe usuário com o id informado"
        ]);

        $transactions = Transaction::where('user_id', $request->id)->cursorPaginate(15);

        return response()->json([
            'user' => $userExists,
            'success' => true,
            'data' => $transactions
        ], 200);
    }

    public function removeTransaction(Request $request)
    {
        $transactionId = $request->id;

        if(!$transactionId) return response()->json([
            "error" => "É necessário informar o id"
        ], 400);

        $transaction = Transaction::find($transactionId);

        if(!$transaction) return response([
            "error" => "Não há transaction com o id informado"
        ],404);

        DB::table('transactions')->where('id', '=', $transactionId)->delete();


        return response()->json(['message' => 'Transaction removida com sucesso'],204);
    }

    public function exportCSVTransactions(Request $request)
    {
        $userId = $request->id;

        if(!$userId) return response()->json([
            "error" => "ID do usuário não informado"
        ]);

        $transactions = [];
        $filtro = $request->input('filter');

        if ($filtro === "last30") {
            $transactions = Transaction::where('created_at', '>=', now()->subDays(30))->where('user_id', '=', $userId)->get();
        } elseif ($filtro == "all") {
            $transactions = Transaction::where('user_id', '=', $userId)->get();
        } else {
            [$mes, $ano] = explode('/', $filtro);
            $transactions = Transaction::where('user_id', '=', $userId)->whereYear('created_at', '=', '20' . $ano)
                                        ->whereMonth('created_at', '=', $mes)
                                        ->with('user')
                                        ->get();
        }

        $csvFileName = "transactions.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
        ];

        $callback = function () use ($transactions) {
            $handleFile = fopen('php://output', 'w');

            $user = $transactions->first()->user ?? null;
            if ($user) {
                fputcsv($handleFile, ['Client Information']);
                fputcsv($handleFile, ['Name', 'Email', 'Birthday', 'Current Balance']);
                fputcsv($handleFile, [
                    $user->name,
                    $user->email,
                    $user->birthday,
                    $user->current_balance ?? '0.00'
                ]);

                fputcsv($handleFile, []);
            }

            fputcsv($handleFile, [
                'User ID',
                'Transaction Value',
                'Transaction Type',
                'Currency',
                'Transaction Date'
            ]);

            foreach ($transactions as $transaction) {
                fputcsv($handleFile, [
                    $transaction->user_id,
                    $transaction->value,
                    $transaction->type,
                    $transaction->currency,
                    $transaction->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handleFile);
        };

        return response()->stream($callback, 200, $headers);
    }
}
