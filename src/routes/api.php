<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::namespace('Api')->group(function(){
    
    Route::prefix('auth')->group(function(){
        Route::post('login', [AuthController::class, 'login']);
        Route::post('signup', [AuthController::class, 'signup']);
        Route::get('commands', [AuthController::class, 'commands'])
    });

    Route::group([
        'middleware'=>'auth:api'
    ], function(){
        Route::get('logout', [AuthController::class, 'logout']);
        // Route::get('user', 'AuthController@user');
        Route::get('helloworld', [AuthController::class, 'index']);
    });
});
