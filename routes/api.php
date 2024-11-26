<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiProductController;
use App\Http\Controllers\PrefContrller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\TestController;

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

//Route::group(['middleware' => 'auth:exam'], function () {
//});
// sanctumでtokenが有効時のみアクセス可能
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::apiResource('products', ApiProductController::class);
    Route::get("exam/test", [ExamController::class, 'test']);
    Route::post("exam/getExamData", [ExamController::class, 'getExamData']);
    Route::post("exam/editExamData", [ExamController::class, 'editExamData']);
    Route::post("exam/getTestExamMenu", [ExamController::class, 'getTestExamMenu']);
    Route::post("exam/getExamTestParts", [ExamController::class, 'getExamTestParts']);
    Route::post("exam/getPFS", [ExamController::class, 'getPFS']);
    Route::post("exam/setPFS", [ExamController::class, 'setPFS']);
    Route::post("exam/editPFS", [ExamController::class, 'editPFS']);
    Route::post("exam/resultPFS", [ExamController::class, 'resultPFS']);


    Route::post('user/admin', [UserController::class, 'getAdmin']);
    Route::post('user/adminEdit', [UserController::class, 'editAdmin']);
    Route::post('user/setUserData', [UserController::class, 'setUserData']);
    Route::post('user/editUserData', [UserController::class, 'editUserData']);
    Route::post('user/setUserLicense', [UserController::class, 'setUserLicense']);
    Route::get('user/checkEmail', [UserController::class, 'checkEmail']);
    Route::get('user/checkLoginID', [UserController::class, 'checkLoginID']);
    Route::post('user/getPartner', [UserController::class, 'getPartner']);
    Route::post('user/getPartnerDetail', [UserController::class, 'getPartnerDetail']);
    Route::post('user/editPartner', [UserController::class, 'editPartner']);
    Route::post('user/getPartnerid', [UserController::class, 'getPartnerid']);
    Route::post('user/setCustomerAdd', [UserController::class, 'setCustomerAdd']);
    Route::post("logout", [UserController::class, 'logout']);
    Route::post("user/getCustomerList", [UserController::class, 'getCustomerList']);
    Route::post("user/getLisencesList", [UserController::class, 'getLisencesList']);
    Route::post("user/getUserLisence", [UserController::class, 'getUserLisence']);
    Route::post("user/getUserLisenceCalc", [UserController::class, 'getUserLisenceCalc']);
    Route::post("test/setTest", [TestController::class, 'setTest']);
    Route::post("test/getTestList", [TestController::class, 'getTestList']);
    Route::post("test/getQRParam", [TestController::class, 'getQRParam']);
    Route::post("test/getQRLists", [TestController::class, 'getQRLists']);
});
Route::post("user/upload", [UserController::class, 'upload']);
Route::post("login", [UserController::class, 'index']);
Route::apiResource('pref', PrefContrller::class);
Route::apiResource('element', ElementController::class);
Route::apiResource('license', LicenseController::class);
Route::apiResource('pdf', PdfController::class);
Route::get('test', [UserController::class, 'test']);



Route::post("exam/login", [ExamController::class, 'index']);
Route::post("exam/getExam", [ExamController::class, 'getExam']);

