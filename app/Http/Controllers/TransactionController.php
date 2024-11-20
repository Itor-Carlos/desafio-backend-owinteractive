<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\error;

class TransactionController extends Controller
{
    //

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
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id'
        ], [
            'id.required' => "É necessário informar o id do usuário",
            'id.integer' => "O tipo do 'id' deve ser integer",
            'id.exists' => "Não existe usuário com o id informado"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $transactions = Transaction::where('user_id', $request->id)->cursorPaginate(2);

        return response()->json([
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

}
