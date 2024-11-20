<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Test;
use Exception;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    function index(Request $request)
    {

        $passwd = config('const.consts.PASSWORD');
        $userdata = Exam::where('email', $request->email)->where("test_id",$request->test_id)->first();
        $user = Exam::find($userdata[ 'id' ]);
        // パスワードがデフォルト状態(password)の時、パスワードの再設定を行う
        if(openssl_decrypt($user['password'], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']) == "password"){
            $pwd = openssl_encrypt($request->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
            $user->password = $pwd;
            $user->save();

            $user = Exam::find($userdata[ 'id' ]);

        }
        $token = "";
        if (openssl_decrypt($user['password'], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']) == $request->password) {
            $token = $user->createToken('my-app-token')->plainTextToken;
            $response = [
                'user' => $user,
                'token' => $token
            ];
            return response($response, 201);
        }
        return response("error", 401);
    }
    function getExam(Request $request){
        $now = date("Y-m-d H:i:s");
        try{
            $rlt = Test::
            select(["users.company_name","tests.*"])
            ->where("params",$request->params)
            ->where("status",1)
            ->where("startdaytime","<=",$now)
            ->where("enddaytime",">=",$now)
            ->leftjoin("users","users.id","=","tests.user_id")
            ->first();
            if($rlt){
                return response($rlt, 200);
            }else{
                return response([],400);
            }
        }catch(Exception $e){
            return response([],400);
        }
    }
    function test()
    {
        $loginUser = auth()->user()->currentAccessToken();
        return response($loginUser, 200);
    }
}
