<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    //
    function index(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        // print_r($data);
        // var_dump($request->password);
        // var_dump($user->password);
        // exit();
        // if (!$user || !Hash::check($request->password, $user->password)) {
        //     return response([
        //         'message' => ['These credentials do not match our records.']
        //     ], 404);
        // }

        $token = $user->createToken('my-app-token')->plainTextToken;
        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }
    function logout(){
        auth('sanctum')->user()->tokens()->delete();
        return response(['message' => 'You have been successfully logged out.'], 200);
}
