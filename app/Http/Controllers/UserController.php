<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userlisence;
use App\Models\userpdf;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class UserController extends Controller
{
    //
    function index(Request $request)
    {
        $passwd = config('const.consts.PASSWORD');
        $userdata = User::where('email', $request->email)->first();
        $user = User::find($userdata[ 'id' ]);

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
    function test(){
        return response("success", 200);
    }
    function upload(Request $request){
        $filename = uniqid().time().".jpg";
        $request->photo->storeAs('public/app/myImage', $filename);
        return response($filename, 201);
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
        try{
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
        }catch(\Exception $e){
            return response([], 400);
        }
    }

    function getPartnerForCustomer($data)
    {
        $user = User::where('type', $data['type'])
        ->where('id',$data['partner_id'])
        ->where('admin_id',$data['admin_id'])
        ->first();
        $response = [
            'user' => $user,
        ];

        return $response;
    }
    function editPartner(Request $request)
    {
        $response = true;
        // ログインしているユーザー情報取得
        $loginUser = auth()->user()->currentAccessToken();
        $passwd = config('const.consts.PASSWORD');
        DB::beginTransaction();
        try{

            $params = [
               // 'name' => $request['name'],
               // 'email' => $request['email'],
                'password' => openssl_encrypt($request['password'], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']),
                'post_code' => $request['post_code'],
                'pref' => $request['pref'],
                'address1' => $request['address1'],
                'address2' => $request['address2'],
                'tel' => $request['tel'],
                'fax' => $request['fax'],
                'person' => $request['person'],
                'person_address' => $request['person_address'],
                'person2' => $request['person2'],
                'person_address2' => $request['person_address2'],
                'person_tel' => $request['person_tel'],
                'system_name' => $request['system_name'],

            ];
            if(!$request['password']){
                unset($params['password']);
            }
            User::where('id', $request['id'])
            ->where('admin_id', $loginUser->tokenable->id)
            ->update($params);

            DB::commit();
            return response("success", 200);
        } catch (\Exception $exception){
            DB::rollback();
            throw $exception;
        }

        return response($response, 201);
    }
    function getPartnerDetail(Request $request)
    {

        // ログインしているユーザー情報取得
        $loginUser = auth()->user()->currentAccessToken();
        // 顧客画面から利用の場合は親のIDを取得
        $partner_id = $request->partnerId;
        try{
            $type = $request->type;
            if($request->type == "customer"){
                $customer = User::where('type', $request->type)
                ->where('id',$partner_id)
                ->select("partner_id")
                ->first();
                $partner_id = $customer->partner_id;
                $type = "partner";
            }
            if($request->type == "guest"){
                $type = "customer";
            }
            /*
            else{
                $type = "customer";
            }
            */
            // 今選択しているパートナー情報の取得

            $user = User::where('type', $type)
            ->where('id',$partner_id);
            if($loginUser->tokenable->type == "admin"){
                $user = $user->where('admin_id',$loginUser->tokenable->id);
            }
            $user = $user->first();
            if(!$user){
                throw new Exception();
            }
            $response = [
                'user' => $user,
            ];
            return response($response, 201);
        }catch(\Exception $e){

            return response($user, 401);
        }
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
        $loginUser = auth()->user()->currentAccessToken();
        DB::beginTransaction();
        try{

        //     $request->validate([
        //         'email' => 'required|unique:users',
        //         //'fax' => 'required',
        //     ]);
            $passwd = config('const.consts.PASSWORD');
            User::insert([
                "admin_id"=>$loginUser->tokenable->id,
                'type' => $request['type'],
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => openssl_encrypt($request['password'], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']),
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
    function editUserData(Request $request)
    {
        $response = true;
        DB::beginTransaction();
        try{

            $user = User::find(5);
            $user->update([
                "name" => "佐藤太郎123",
            ]);



            DB::commit();
            return response('OK', 200);
        } catch (\Exception $exception){
            DB::rollback();
            throw $exception;
        }
    }
    function setCustomerAdd(Request $request){
        $req = $request[ 'type' ];
        try{
            $passwd = config('const.consts.PASSWORD');
            User::insert([
                'type' => $request['type'],
                'admin_id' => $request['admin_id'],
                'partner_id' => $request['partner_id'],
                'name' => $request['name'],
                'email' => $request['email'],
                'login_id' => $request['login_id'],
                'password' => openssl_encrypt($request['password'], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']),
                'company_name' => $request['company_name'],
                'post_code' => $request['post_code'],
                'pref' => $request['pref'],
                'address1' => $request['address1'],
                'address2' => $request['address2'],
                'tel' => $request['tel'],
                'fax' => $request['fax'],
                'trendFlag' => $request['trendFlag'],
                'csvFlag' => $request['csvFlag'],
                'pdfFlag' => $request['pdfFlag'],
                'weightFlag' => $request['weightFlag'],
                'excelFlag' => $request['excelFlag'],
                'customFlag' => $request['customFlag'],
                'sslFlag' => $request['sslFlag'],
                'logoImagePath' => $request['logoImagePath'],
                'privacy'=>$request['privacy'],
                'privacyText'=>$request['privacyText'],
                'displayFlag'=>$request['displayFlag'],
                'tanto_name'=>$request['tanto_name'],
                'tanto_address'=>$request['tanto_address'],
                'tanto_busyo'=>$request['tanto_busyo'],
                'tanto_tel1'=>$request['tanto_tel1'],
                'tanto_tel2'=>$request['tanto_tel2'],
                'tanto_name2'=>$request['tanto_name2'],
                'tanto_address2'=>$request['tanto_address2']
            ]);
            DB::commit();
            return response("success", 201);
        }catch(\Exception $e){
            return response("error", 401);
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
                    if($licensesBody[$key] > 0 ){
                        userlisence::insert([
                            'user_id' => $user_id,
                            'code' => $value,
                            'num' => $licensesBody[$key],
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            /*
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
                */
            DB::commit();
            return response($licensesKey, 200);
        } catch (\Exception $exception){
            DB::rollback();
            throw $exception;
        }
    }

    function getCustomerList(Request $request){
        try{
            $result = User::where("type","customer")->where("partner_id",$request->partner_id)->where("deleted_at",null)
            ->get();
            return response($result, 201);
        }catch(\Exception $e){
            return response([],401);
        }
    }

    function getPartnerid(Request $request){
        try{
            $result = User::select('partner_id')->where("type",$request->type)->where("id",$request->id)->where("deleted_at",null)
            ->first();
            return response($result->partner_id, 201);
        }catch(\Exception $e){
            return response(0,401);
        }
    }
    function getLisencesList(Request $request){
        // $loginUser = auth()->user()->currentAccessToken();
        // var_dump($loginUser->tokenable->id);
        try{
            $result = userlisence::where("user_id",$request->user_id)->orderby("code")->get();
            return response($result, 201);
        }catch(\Exception $e){
            return response([],401);
        }
    }
    function getUserLisence(Request $request){
        $license = $this->getLicenseListsJP();
        $user_id = $request->user_id;
        $loginUser = auth()->user()->currentAccessToken();

        $admin = $loginUser->tokenable;
        $customer = User::find($user_id);
        $partner = User::where("admin_id",$admin->id)->where("id",$customer->partner_id)->where("deleted_at",null)->first();
        $result = userlisence::where("user_id",$partner->id)->orderby("code")->get();
        foreach($result as $k=>$value){
            $result[ $k ][ 'jp' ] = $license[$value[ 'code' ]];
        }
        return response($result,200);
    }
    function getUserLisenceCalc(Request $request){
        $license = $this->getLicenseListsJP();
        $user_id = $request->user_id;
        $loginUser = auth()->user()->currentAccessToken();

        $admin = $loginUser->tokenable;
        $customer = User::find($user_id);
        $partner = User::where("admin_id",$admin->id)->where("id",$customer->partner_id)->where("deleted_at",null)->first();
        $result = userlisence::where("user_id",$partner->id)->orderby("code")->get();
        foreach($result as $k=>$value){
            $result[ $k ][ 'jp' ] = $license[$value[ 'code' ]];
        }
        return response($result,200);
    }
    function getLicenseListsJP(){
        $data = [];
        $license = config('const.consts.LISENCE');
        foreach($license as $value){
            foreach($value['list'] as $val){
                $data[$val['code']] = $val[ 'text' ];
            }
        }
        return $data;
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
