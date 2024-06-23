<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class UserController extends Controller
{
    //
    function index(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        $token = $user->createToken('my-app-token')->plainTextToken;
        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    function getAdmin(Request $request)
    {
        $user = User::where('type', $request->type)->get();
        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }
    function editAdmin(Request $request)
    {
        for ($i = 0; $i <= 3; $i++) {
            User::where('id', $request[$i]['id'])->update(
                [
                    'login_id' => $request[$i]['login_id'],
                    'person' => $request[$i]['person'],
                    'person_address' => $request[$i]['person_address']
                ]
            );
        }
        return response(true, 201);
    }
    function setUserData(Request $request)
    {
        $response = true;
        DB::beginTransaction();
        try{

            $request->validate([
                'email' => 'required|unique:users',
                //'fax' => 'required',
            ]);

            User::insert([
                'type' => $request['type'],
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make($request['password']),
                'company_name' => $request['company_name'],
                'login_id' => $request['login_id'],
                'post_code' => $request['post_code'],
                'pref' => $request['pref'],
                'address1' => $request['address1'],
                'address2' => $request['address2'],
                'tel' => $request['tel'],
                'fax' => $request['fax'],
                'requestFlag' => $request['requestFlag'],
                'person' => $request['person'],
                'person_address' => $request['person_address'],
                'person2' => $request['person2'],
                'person_address2' => $request['person_address2'],
                'person_tel' => $request['person_tel'],
                'system_name' => $request['system_name'],
            ]);
            DB::commit();
        } catch (\Exception $exception){
            DB::rollback();
            throw $exception;
        }
        return response($response, 200);

    }


    function logout()
    {
        auth('sanctum')->user()->tokens()->delete();
        return response(['message' => 'You have been successfully logged out.'], 200);
    }
}
