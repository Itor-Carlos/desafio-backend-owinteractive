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
            'email' => 'required|unique:users|string',
            'birthday' => 'required|date'
        ], [
            'name.required' => "O campo 'nome' é obrigatório.",
            'name.string' => "O campo 'nome' deve ser uma string",
            'email.required' => "O campo 'email' é obrigatório",
            'email.string' => "O campo 'email' deve ser uma string",
            'email.unique' => "Esse email já foi atualizado",
            'birthday.required' => "O campo 'birtday' é obrigatório",
            'birthday.date' => "O campo 'birthday' deve ser um date"
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

        $userCreated = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'birthday' => $request->birthday,
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
}
