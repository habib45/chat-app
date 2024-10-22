<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V1\LoginController;
// use App\Http\Controllers\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::group(['prefix' => 'v1'], function () {
    Route::get('/info', [UserController::class, 'info']);
    Route::POST('/registration', [UserController::class, 'register']);
    Route::POST("login", [LoginController::class, 'login']);
    Route::get("user/{id}", [UserController::class, 'show']);
});