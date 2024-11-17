<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    function index(Request $request)
    {

        $passwd = config('const.consts.PASSWORD');
        $userdata = Exam::where('email', $request->email)->first();
        $user = Exam::find($userdata[ 'id' ]);

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
    function test()
    {
        $loginUser = auth()->user()->currentAccessToken();
        return response($loginUser, 200);
    }
}
