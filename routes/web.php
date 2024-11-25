<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Models\Transaction;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::post('/user',  [UserController::class, 'store']);
Route::get('/user', [UserController::class, 'index']);
Route::get('/user/{id}', [UserController::class, 'getById']);
Route::delete('/user/{id}', [UserController::class, 'delete']);
Route::get('/user/{id}/transaction', [UserController::class, 'sumTransaction']);

Route::post('/transaction', [TransactionController::class, 'store']);
Route::get('/transaction/{id}', [TransactionController::class, 'getTransactions']);
Route::delete('/transaction/{id}', [TransactionController::class, 'removeTransaction']);
Route::get('/transaction/csv/generate', [TransactionController::class, 'exportCSVTransactions']);
