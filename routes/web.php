<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDF\IndexController;
use App\Http\Controllers\examRowDataController;

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

Route::get("pdf/{id?}/code/{code?}/birth/{birth?}/{encode?}", [IndexController::class, 'index'])->name("PFSPDF");
Route::get("certificate/{id?}/code/{code?}/birth/{birth?}/{encode?}", [IndexController::class, 'certificate'])->name("certificatePDF");

// エクセルのダウンロード
Route::get('/excels/{filename}', function ($filename) {
    $file = storage_path('app/excels/' . $filename);
    if (!file_exists($file)) {
        abort(404);
    }
    return response()->download($file);
});

// CSVのダウンロード(検査種別rowデータ)
// PFS
Route::get("examRowData/{code}", [examRowDataController::class, 'index'])->name("examRowData");


//Route::post('/save-radar-image', [PfsController::class, 'saveRadarImage']);
