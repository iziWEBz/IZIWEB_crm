<?php

use App\Modules\IAM\Controllers\AuthController;
use App\Modules\IAM\Controllers\UserController; 
use Illuminate\Support\Facades\Route;


Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::middleware('role:admin')->group(function () {
        Route::get('iam/roles', [UserController::class, 'rolesList']);
        Route::get('iam/users', [UserController::class, 'index']);
        Route::get('iam/users/{id}', [UserController::class, 'show']);
        Route::post('iam/users/store', [UserController::class, 'store']);
        Route::put('iam/users/{id}/update', [UserController::class, 'update']);
        Route::delete('iam/users/{id}', [UserController::class, 'destroy']);
    });
});
