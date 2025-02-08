<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDF\PfsController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get("pdf/{id?}/code/{code?}/birth/{birth?}", [PfsController::class, 'index'])->name("PFSPDF");
//Route::post('/save-radar-image', [PfsController::class, 'saveRadarImage']);
