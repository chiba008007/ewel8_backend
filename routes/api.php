<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiProductController;
use App\Http\Controllers\PrefContrller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\LicenseController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Route::apiResource('products', ApiProductController::class);

// sanctumでtokenが有効時のみアクセス可能
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::apiResource('products', ApiProductController::class);
    Route::post('user/admin', [UserController::class, 'getAdmin']);
    Route::post('user/adminEdit', [UserController::class, 'editAdmin']);
    Route::post("logout", [UserController::class, 'logout']);
});

Route::post("login", [UserController::class, 'index']);
Route::apiResource('pref', PrefContrller::class);
Route::apiResource('element', ElementController::class);
Route::apiResource('license', LicenseController::class);
