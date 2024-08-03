<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userlisence;
use App\Models\userpdf;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class UserController extends Controller
{
    //
    function index(Request $request)
    {

        $userdata = User::where('email', $request->email)->first();
        $user = User::find($userdata[ 'id' ]);

        $token = "";
        if (password_verify($request->password, $user['password'])) {
            $token = $user->createToken('my-app-token')->plainTextToken;


            $response = [
                'user' => $user,
                'token' => $token
            ];

            return response($response, 201);
        }

        return response([], 401);

    }

    function getAdmin(Request $request)
    {
        $user = User::where('type', $request->type)->get();
        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }
    function getPartner(Request $request)
    {
        $user = User::where('type', $request->type)
        ->select('users.*')
        ->orderBy('users.updated_at','DESC')
        ->selectRaw('SUM(userlisences.num) as total')
        ->leftjoin('userlisences', 'users.id', '=', 'userlisences.user_id')
        ->groupBy('users.id')
        ->get();
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

        //     $request->validate([
        //         'email' => 'required|unique:users',
        //         //'fax' => 'required',
        //     ]);

            User::insert([
                'type' => $request['type'],
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => password_hash($request['password'],PASSWORD_DEFAULT),
                // 'company_name' => $request['company_name'],
                // 'login_id' => $request['login_id'],
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
                'element1' => $request['element1'],
                'element2' => $request['element2'],
                'element3' => $request['element3'],
                'element4' => $request['element4'],
                'element5' => $request['element5'],
                'element6' => $request['element6'],
                'element7' => $request['element7'],
                'element8' => $request['element8'],
                'element9' => $request['element9'],
                'element10' => $request['element10'],
                'element11' => $request['element11'],
                'element12' => $request['element12'],
            ]);

            $id = DB::getPdo()->lastInsertId();

            DB::commit();
            return response($id, 200);
        } catch (\Exception $exception){
            DB::rollback();
            throw $exception;
        }
    }
    function setUserLicense(Request $request)
    {
        DB::beginTransaction();
        try{
            $user_id = $request['res']['data'];
            $licensesKey = $request['licensesKey'];
            $licensesBody = $request['licensesBody'];
            $pdfList = $request['pdfList'];
            foreach($licensesKey as $key=>$value){
                $license = userlisence::where('code', $value)->where('user_id',$user_id)->first();
                if($license){
                    $data = userlisence::find($license->id);
                    $data->update([
                        'num' => $licensesBody[$key],
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                }else{
                    userlisence::insert([
                        'user_id' => $user_id,
                        'code' => $value,
                        'num' => $licensesBody[$key],
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                }
            }
            foreach($pdfList as $key=>$value){
                $license = userpdf::where('code', $value)->where('user_id',$user_id)->first();
                if($license){
                    $data = userpdf::find($license->id);
                    $data->update([
                        'num' => $licensesBody[$key],
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                }else{
                    userpdf::insert([
                        'user_id' => $user_id,
                        'code' => $value,
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                }
            }
            DB::commit();
            return response($licensesKey, 200);
        } catch (\Exception $exception){
            DB::rollback();
            throw $exception;
        }
    }
    function checkEmail(Request $request){
        $email = $request['email'];
        $user = User::where('email', $email)->first();
        if($user){
            // すでにメールが登録されている
            return response(true, 200);
        }else{
            return response(true, 400);
        }
    }
    function logout()
    {
        auth('sanctum')->user()->tokens()->delete();
        return response(['message' => 'You have been successfully logged out.'], 200);
    }
}
