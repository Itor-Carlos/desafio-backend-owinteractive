<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|max:75|string',
            'email' => 'required|email|unique:users|string',
            'birthday' => 'required|date',
            'initial_balance' => 'nullable|numeric|min:0'
        ], [
            'name.required' => "O campo 'nome' é obrigatório.",
            'name.string' => "O campo 'nome' deve ser uma string.",
            'email.required' => "O campo 'email' é obrigatório.",
            'email.string' => "O campo 'email' deve ser uma string.",
            'email.unique' => "Esse email já está em uso.",
            'email.email' => "O campo 'email' deve conter um endereço válido.",
            'birthday.required' => "O campo 'data de nascimento' é obrigatório.",
            'birthday.date' => "O campo 'data de nascimento' deve ser uma data válida.",
            'initial_balance.numeric' => "O campo 'saldo inicial' deve ser um número.",
            'initial_balance.min' => "O campo 'saldo inicial' não pode ser negativo."
        ]);

        if ($validateRequest->fails()) {
            $erros = $validateRequest->errors();
            $errosFormatados = [];

            foreach ($erros->toArray() as $campo => $mensagens) {
                $errosFormatados[$campo] = implode(', ', $mensagens);
            }

            return response()->json($errosFormatados, 400);
        }

        $birthday = Carbon::parse($request->birthday);
        $age = $birthday->diffInYears(Carbon::now());

        if ($age < 18) {
            return response()->json([
                "message" => "Necessário idade superior ou igual a 18 anos"
            ], 400);
        }

        $initialBalance = $request->input('initial_balance', 0.00);

        $userCreated = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'birthday' => $request->birthday,
            'initial_balance' => $initialBalance
        ]);

        return response()->json($userCreated, 201);
    }

    public function index(){
        $users = DB::table('users')->orderBy('created_at', 'desc')->get();
        return response()->json($users, 200);
    }

    public function getById(Request $request){
        if(!$request->id) return response()->json("O id do usuário não foi informado",200);

        $user = User::find($request->id);

        if($user) return response($user, 200);
        return response([
            "error" => "Não existe usuário cadastrado com o id informado"
        ],404);
    }

    public function delete(Request $request){
        if(!$request->id) return response()->json("Id não informado", 400);

        $user = User::find($request->id);
        if($user){
            $transactionsUser = Transaction::where('user_id', '=', $user->id)->get();

            if(count($transactionsUser)!=0) return response()->json([
                "message" => "Não é possível deletar o usuário de ID ".$user->id."devido ao mesmo ter transações"
            ],406);

            DB::table("users")->where("id", "=", $request->id)->delete();
            return response()->json([
                "mensagem" => "Usuário removido com sucesso"
            ], 200);
        }

        return response()->json([
            "error" => "Não existe usuário com id informado"
        ], 404);
    }

    public function sumTransaction(Request $request){

        if(!$request->id) return response([
            "message" => "O id do usuário não foi informado"
        ],400);

        $user = User::find($request->id);

        if(!$user) return response([
            "message" => "Não existe nenhum usuário com o id informado"
        ]);

        $transactionsUser = Transaction::where('user_id', '=', $request->id)->get();
        $value = $user->initial_balance;

        foreach($transactionsUser as $transaction){
            $value += $transaction->value;
        }
        return response()->json([
            "sum_transactions" => $value
        ],200);
    }
}
