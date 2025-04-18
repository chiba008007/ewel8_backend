<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiProductController;
use App\Http\Controllers\createSpredsheetController;
use App\Http\Controllers\PrefContrller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CsvsController;
use App\Http\Controllers\FileuploadsController;
use App\Http\Controllers\PDF\PfsController;
use App\Http\Controllers\WeightController;

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
    Route::post("exam/getTestDataExam", [ExamController::class, 'getTestDataExam']);
    Route::post("exam/getExamTestParts", [ExamController::class, 'getExamTestParts']);
    Route::post("exam/getPFS", [ExamController::class, 'getPFS']);
    Route::post("exam/setPFS", [ExamController::class, 'setPFS']);
    Route::post("exam/editPFS", [ExamController::class, 'editPFS']);
    Route::post("exam/resultPFS", [ExamController::class, 'resultPFS']);
    Route::post("exam/checkStatus", [ExamController::class, 'checkStatus']);
    Route::post("exam/getExamList", [ExamController::class, 'getExamList']);
    Route::post("exam/downloadExam", [ExamController::class, 'downloadExam']);
    Route::post("exam/setStarttime", [ExamController::class, 'setStarttime']);



    Route::post('user/admin', [UserController::class, 'getAdmin']);
    Route::post('user/adminEdit', [UserController::class, 'editAdmin']);
    Route::post('user/setUserData', [UserController::class, 'setUserData']);
   // Route::post('user/editUserData', [UserController::class, 'editUserData']);
    Route::post('user/editPartnerData', [UserController::class, 'editPartnerData']);
    Route::post('user/setUserLicense', [UserController::class, 'setUserLicense']);
    Route::get('user/checkEmail', [UserController::class, 'checkEmail']);
    Route::get('user/checkLoginID', [UserController::class, 'checkLoginID']);
    Route::post('user/getPartner', [UserController::class, 'getPartner']);
    Route::post('user/getPartnerDetail', [UserController::class, 'getPartnerDetail']);
    Route::post('user/getPartnerDetailData', [UserController::class, 'getPartnerDetailData']);
    Route::post('user/editPartner', [UserController::class, 'editPartner']);
    Route::post('user/getPartnerid', [UserController::class, 'getPartnerid']);
    Route::post('user/setCustomerAdd', [UserController::class, 'setCustomerAdd']);
    Route::post('user/getUserElement', [UserController::class, 'getUserElement']);
    Route::post('user/getUserData', [UserController::class, 'getUserData']);
    Route::post('user/customerEdit', [UserController::class, 'customerEdit']);

    Route::post("logout", [UserController::class, 'logout']);
    Route::post("user/getCustomerList", [UserController::class, 'getCustomerList']);
    Route::post("user/getLisencesList", [UserController::class, 'getLisencesList']);
    Route::post("user/getUserLisence", [UserController::class, 'getUserLisence']);
    Route::post("user/getUserLisenceCalc", [UserController::class, 'getUserLisenceCalc']);
    Route::post("test/setTest", [TestController::class, 'setTest']);
    Route::post("test/editTest", [TestController::class, 'editTest']);
    Route::post("test/getTestList", [TestController::class, 'getTestList']);
    Route::post("test/getQRParam", [TestController::class, 'getQRParam']);
    Route::post("test/getQRLists", [TestController::class, 'getQRLists']);
    Route::post("test/getTestDetail", [TestController::class, 'getTestDetail']);
    Route::post("test/getTestEditData", [TestController::class, 'getTestEditData']);
    Route::post("test/getCsvList", [TestController::class, 'getCsvList']);
    Route::post("test/getTestTableTh", [TestController::class, 'getTestTableTh']);
    Route::post("test/getPFSTestDetail", [TestController::class, 'getPFSTestDetail']);
    Route::post("test/getSearchExam", [TestController::class, 'getSearchExam']);

    Route::post("weight/editStatusWeightMaster", [WeightController::class, 'editStatusWeightMaster']);
    Route::post("weight/editWeightMaster", [WeightController::class, 'editWeightMaster']);
    Route::post("weight/setWeightMaster", [WeightController::class, 'setWeightMaster']);
    Route::post("weight/getWeightMaster", [WeightController::class, 'getWeightMaster']);
    Route::post("weight/getWeightMasterDetail", [WeightController::class, 'getWeightMasterDetail']);


    Route::post("csv/getPfs", [CsvsController::class, 'getPfs']);
    Route::post("excel/create", [createSpredsheetController::class, 'create']);
    Route::post("user/fileupload", [UserController::class, 'fileupload']);
    Route::post("fileupload/list", [FileuploadsController::class, 'list']);
    Route::post("fileupload/openFlag", [FileuploadsController::class, 'openFlag']);
    Route::post("fileupload/deleteStatus", [FileuploadsController::class, 'deleteStatus']);


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

