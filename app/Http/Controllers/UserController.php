<?php

namespace App\Http\Controllers;

use App\Mail\CustomerAddMail;
use App\Mail\CustomerAddMailPassword;
use App\Models\fileuploads;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userlisence;
use App\Models\userpdf;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\SetUserDataMail;
use App\Mail\EditUserDataMail;
use App\Mail\SetUserDataMailPassword;
use App\Mail\EditPartnerEditMail;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public const G_ADMIN = "admin";
    public const G_PARTNER = "partner";
    public function checkAdmin()
    {
        $loginUser = auth()->user()->currentAccessToken();
        if ($loginUser->tokenable->type != self::G_ADMIN) {
            throw new Exception();
        }
    }
    //
    public function index(Request $request)
    {

        $passwd = config('const.consts.PASSWORD');
        $userdata = User::where('login_id', $request->login_id)->first();
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
    public function test()
    {
        return response("success", 200);
    }
    public function upload(Request $request)
    {
        $filename = uniqid().time().".jpg";
        $request->photo->storeAs('public/app/myImage', $filename);
        return response($filename, 201);
    }
    public function fileupload(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $dir = "public/files/file".$request->editid."/";
        Storage::makeDirectory($dir);
        $file = $request->file('file');
        $path = $file->store('uploads', 'public');
        $filename = $_FILES['file']['name'];
        $size = $_FILES['file']['size'];

        $params = [];
        $params[ 'partner_id' ] = $request->partner_id;
        $params[ 'customer_id'] = $request->customer_id;
        $params[ 'admin_id' ] = $loginUser->tokenable->id;
        $params[ 'filename' ] = $filename;
        $params[ 'filepath' ] = $path;
        $params[ 'size' ] = $size;
        $params[ 'status' ] = 1;
        $params[ 'created_at' ] = date("Y-m-d H:i:s");
        $params[ 'updated_at' ] = date("Y-m-d H:i:s");

        fileuploads::insert($params);
        return response($loginUser, 201);
    }
    public function getAdmin(Request $request)
    {
        $user = User::where('type', $request->type)->get();
        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }
    public function getPartner(Request $request)
    {
        try {
            $this->checkAdmin();
            $sql = "
SELECT
    *,
    (jyuken - syori) AS zan,
    (total - syori - jyuken) AS buy
FROM (
    SELECT
        users.id,
        users.name,
        (
            SELECT SUM(userlisences.num)
            FROM userlisences
            WHERE userlisences.user_id = users.id
        ) AS total,
        COUNT(CASE WHEN exams.deleted_at IS NULL THEN exams.id ELSE NULL END) AS jyuken,
        (
            SELECT COUNT(exams.id)
            FROM exams
            WHERE
                exams.partner_id = users.id AND
                started_at IS NOT NULL AND
                exams.deleted_at IS NULL
        ) AS syori
    FROM
        users
        LEFT JOIN exams ON exams.partner_id = users.id
    WHERE
        users.type = ?
    GROUP BY
        users.id
) AS a


                    ";
            $param = [];
            $param[] = $request->type;
            $response = DB::select($sql, $param);
            return response($response, 201);
        } catch (\Exception $e) {
            return response([], 201);
        }
    }

    public function getPartnerForCustomer($data)
    {
        $user = User::where('type', $data['type'])
        ->where('id', $data['partner_id'])
        ->where('admin_id', $data['admin_id'])
        ->first();
        $response = [
            'user' => $user,
        ];

        return $response;
    }
    public function editPartner(Request $request)
    {
        Log::info('企業情報変更:'.$request);
        $response = true;
        // ログインしているユーザー情報取得
        $loginUser = auth()->user()->currentAccessToken();
        $passwd = config('const.consts.PASSWORD');
        DB::beginTransaction();


        try {

            $customer = User::where("type", "partner")
                    ->where('id', $request->id)
                    ->select("login_id")
                    ->first();
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
//                'system_name' => $request['system_name'],

            ];
            if (!$request['password']) {
                unset($params['password']);
            }
            User::where('id', $request->id)
           //->where('admin_id', $loginUser->tokenable->id)
            ->update($params);

            Log::info('企業情報変更メール配信');

            $mailbody = [];
            $mailbody[ 'title' ] = "【Welcome-k】 企業情報変更のお知らせ";
            $mailbody[ 'name' ] = $request->name;
            $mailbody[ 'systemname' ] = $request->systemname;
            $mailbody[ 'login_id' ] = $customer->login_id;

            if ($request->person_address) {
                $mailbody[ 'person' ] = $request->person;
                Mail::to($request->person_address)->send(new EditPartnerEditMail($mailbody));
                Log::info('企業情報変更メール配信担当者1');
            }
            if ($request->person_address2) {
                $mailbody[ 'person' ] = $request->person2;
                Mail::to($request->person_address2)->send(new EditPartnerEditMail($mailbody));
                Log::info('企業情報変更メール配信担当者2');
            }


            DB::commit();
            Log::info('企業情報変更成功:'.$request);
            return response("success", 200);
        } catch (\Exception $exception) {
            Log::info('企業情報変更失敗:'.$exception);
            DB::rollback();
            throw $exception;
        }

        return response($response, 201);
    }
    public function getPartnerDetailData(Request $request)
    {
        // ログインしているユーザー情報取得
        $loginUser = auth()->user()->currentAccessToken();
        $customer = User::where("type", "partner");
        if ($loginUser->tokenable->type === "partner") {
            $customer->where("id", $loginUser->tokenable->id);
        } else {
            $customer->where('id', $request->partnerId);
            $customer->where("admin_id", $loginUser->tokenable->id);
        }
        $customer = $customer->first();

        return response($customer, 200);
    }
    public function getPartnerDetail(Request $request)
    {
        $response = [];
        // ログインしているユーザー情報取得
        $loginUser = auth()->user()->currentAccessToken();
        // 顧客画面から利用の場合は親のIDを取得
        $partner_id = $request->partnerId;
        $editid = $request->editId;
        try {
            $type = $request->type;
            // パートナー情報取得
            $user = [];
            if ($type === "partner") {
                $user = User::where('type', $type)->where("id", $editid)->first();
            }
            if ($type === "customer") {
                $customer = User::where("type", "customer")
                    ->where('id', $editid);
                $user = $customer->first();
            }
            if ($type === "customerTOP") { // 顧客管理画面一覧
                $customer = User::where("type", "partner")
                    ->where('id', $partner_id);
                if ($loginUser->tokenable->type == "admin") {
                    $customer->where("admin_id", $loginUser->tokenable->id);
                }
                $user = $customer->first();
            }

            return response($user, 201);
        } catch (\Exception $e) {

            return response("error", 401);
        }
    }
    public function editAdmin(Request $request)
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
    public function setUserData(Request $request)
    {
        Log::info('新規パートナー登録の実施:'.$request);
        $passwd = config('const.consts.PASSWORD');

        $response = true;
        $loginUser = auth()->user()->currentAccessToken();
        DB::beginTransaction();
        try {

            //     $request->validate([
            //         'email' => 'required|unique:users',
            //         //'fax' => 'required',
            //     ]);

            User::insert([
                "admin_id" => $loginUser->tokenable->id,
                'type' => $request['type'],
                'login_id' => $request['login_id'],
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
                'element13' => $request['element13'],
                'element14' => $request['element14'],
            ]);

            $user_id = DB::getPdo()->lastInsertId();

            $this->setLicensed($request, $user_id);


            Log::info('新規パートナー登録の実施成功:'.$request);
            // 情報登録メール
            $mailbody = [];
            $mailbody[ 'title' ] = "【Welcome-k】 企業情報登録のお知らせ";
            $mailbody[ 'name' ] = $request->name;
            $mailbody[ 'systemname' ] = $request->systemname;
            $mailbody[ 'login_id' ] = $request->login_id;
            $mailbody[ 'licensesBody' ] = array_sum($request->licensesBody);
            if ($request->person_address) {
                $mailbody[ 'person' ] = $request->person;
                $mailbody[ 'password' ] = $request['password'];
                Mail::to($request->person_address)->send(new SetUserDataMail($mailbody));
                Mail::to($request->person_address)->send(new SetUserDataMailPassword($mailbody));
                Log::info('新規パートナー登録メール配信担当者1');
            }
            if ($request->person_address2) {
                $mailbody[ 'person' ] = $request->person2;
                $mailbody[ 'password' ] = $request['password'];
                Mail::to($request->person_address2)->send(new SetUserDataMail($mailbody));
                Mail::to($request->person_address2)->send(new SetUserDataMailPassword($mailbody));
                Log::info('新規パートナー登録メール配信担当者2');
            }
            DB::commit();
            return response("success", 200);
        } catch (\Exception $exception) {
            Log::error('新規パートナー登録の実施失敗:'.$request);
            DB::rollback();
            throw $exception;
        }
    }
    public function setLicensed($data, $user_id)
    {
        Log::info('ライセンス登録関数の実施:user_id:'.$user_id.":".$data);
        $licensesKey = $data['licensesKey'];
        $licensesBody = $data['licensesBody'];
        foreach ($licensesKey as $key => $value) {
            $license = userlisence::where('code', $value)->where('user_id', $user_id)->first();
            if ($license) {
                $data = userlisence::find($license->id);
                $data->update([
                    'num' => $licensesBody[$key],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                if ($licensesBody[$key] > 0) {
                    userlisence::insert([
                        'user_id' => $user_id,
                        'code' => $value,
                        'num' => $licensesBody[$key],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }
    public function editPartnerData(Request $request)
    {
        Log::info('editPartnerData実施:'.$request);
        DB::beginTransaction();
        try {
            $loginUser = auth()->user()->currentAccessToken();
            $admin_id = $loginUser->tokenable->id;
            $user = User::where("id", $request->id)->where("admin_id", $admin_id)->where("type", $request->type);
            if ($request->password) {
                $passwd = config('const.consts.PASSWORD');
                $user->update([
                        "password" => openssl_encrypt($request->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv'])
                    ]);
            }
            if ($request->person) {
                $user->update(["person" => $request->person]);
            }
            if ($request->person_address) {
                $user->update(["person_address" => $request->person_address]);
            }
            $user->update([
                "post_code" => $request->post_code,
                "pref" => $request->pref,
                "address1" => $request->address1,
                "address2" => $request->address2,
                "tel" => $request->tel,
                "fax" => $request->fax,
                "requestFlag" => $request->requestFlag,
                "person" => $request->person,
                "person_address" => $request->person_address,
                "person2" => $request->person2,
                "person_address2" => $request->person_address2,
                "person_tel" => $request->person_tel,
                "system_name" => $request->system_name,
                "element1" => $request->element1,
                "element2" => $request->element2,
                "element3" => $request->element3,
                "element4" => $request->element4,
                "element5" => $request->element5,
                "element6" => $request->element6,
                "element7" => $request->element7,
                "element8" => $request->element8,
                "element9" => $request->element9,
                "element10" => $request->element10,
                "element11" => $request->element11,
                "element12" => $request->element12,
                "element13" => $request->element13,
                "element14" => $request->element14,
            ]);

            $this->setLicensed($request, $request->id);

            Log::info('更新パートナーの実施成功:'.$request);
            // 情報登録メール
            $mailbody = [];
            $mailbody[ 'title' ] = "【Welcome-k】 企業情報更新のお知らせ";
            $mailbody[ 'name' ] = $request->name;
            $mailbody[ 'systemname' ] = $request->systemname;
            $mailbody[ 'licensesBody' ] = array_sum($request->licensesBody);
            if ($request->person_address) {
                Log::info('更新パートナーへメール:'.$request->person_address);
                $mailbody[ 'person' ] = $request->person;
                Mail::to($request->person_address)->send(new EditUserDataMail($mailbody));
            }
            if ($request->person_address2) {
                Log::info('更新パートナーへメール:'.$request->person_address2);
                $mailbody[ 'person' ] = $request->person2;
                Mail::to($request->person_address2)->send(new EditUserDataMail($mailbody));
            }

            DB::commit();
            return response("success", 200);
        } catch (\Exception $exception) {
            DB::rollback();
            throw $exception;
        }
    }



    public function setCustomerAdd(Request $request)
    {
        $req = $request[ 'type' ];
        Log::info('新規顧客登録開始:'.$request);
        try {

            $partner = User::where("type", "partner")
                    ->where('id', $request->partner_id)
                    ->select("system_name")
                    ->first();
            if (!$partner) {
                throw new Exception();
            }
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
                'privacy' => $request['privacy'],
                'privacyText' => $request['privacyText'],
                'displayFlag' => $request['displayFlag'],
                'tanto_name' => $request['tanto_name'],
                'tanto_address' => $request['tanto_address'],
                'tanto_busyo' => $request['tanto_busyo'],
                'tanto_tel1' => $request['tanto_tel1'],
                'tanto_tel2' => $request['tanto_tel2'],
                'tanto_name2' => $request['tanto_name2'],
                'tanto_address2' => $request['tanto_address2']
            ]);

            $mailbody = [];
            $mailbody[ 'title' ] = "【Welcome-k】 企業情報登録のお知らせ";
            $mailbody[ 'name' ] = $request['name'];
            $mailbody[ 'systemname' ] = $partner->system_name;
            $mailbody[ 'login_id' ] = $request['login_id'];
            if ($request['tanto_address']) {
                $mailbody[ 'person' ] = $request['tanto_name'];
                $mailbody[ 'password' ] = $request['password'];
                Mail::to($request['tanto_address'])->send(new CustomerAddMail($mailbody));
                Mail::to($request['tanto_address'])->send(new CustomerAddMailPassword($mailbody));
                Log::info('新規顧客登録メール配信担当者1');
            }
            if ($request['tanto_address2']) {
                $mailbody[ 'person' ] = $request['tanto_name2'];
                $mailbody[ 'password' ] = $request['password'];
                Mail::to($request['tanto_address2'])->send(new CustomerAddMail($mailbody));
                Mail::to($request['tanto_address2'])->send(new CustomerAddMailPassword($mailbody));
                Log::info('新規顧客登録メール配信担当者2');
            }

            Log::info('新規顧客登録成功');
            DB::commit();
            return response("success", 200);
        } catch (\Exception $e) {
            Log::info('新規顧客登録失敗'.$e);
            return response("error", 400);
        }
    }
    public function setUserLicense(Request $request)
    {
        DB::beginTransaction();
        try {
            $user_id = $request['res']['data'];
            $licensesKey = $request['licensesKey'];
            $licensesBody = $request['licensesBody'];
            $pdfList = $request['pdfList'];
            foreach ($licensesKey as $key => $value) {
                $license = userlisence::where('code', $value)->where('user_id', $user_id)->first();
                if ($license) {
                    $data = userlisence::find($license->id);
                    $data->update([
                        'num' => $licensesBody[$key],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    if ($licensesBody[$key] > 0) {
                        userlisence::insert([
                            'user_id' => $user_id,
                            'code' => $value,
                            'num' => $licensesBody[$key],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
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
        } catch (\Exception $exception) {
            DB::rollback();
            throw $exception;
        }
    }

    public function getCustomerList(Request $request)
    {
        try {
            // パートナー権限でログインしたとき、パラメータとIDが一致しないときはエラー
            $loginUser = auth()->user()->currentAccessToken();
            $id = $loginUser->tokenable->id;
            $partner_id = $request->partner_id;
            if ($loginUser->tokenable->type === 'partner') {
                $id = $loginUser->tokenable->admin_id;

                $partner_id = $loginUser->tokenable->id;
                if ($partner_id != $request->partner_id) {
                    // 表示させない画面
                    throw new Exception();
                }
            }

            $subQuery = User::select('users.*')
            ->selectRaw('count(exams.id) AS count')
            ->selectRaw('(SELECT count(exams.id) FROM exams WHERE started_at IS NOT NULL AND  deleted_at IS NULL AND exams.customer_id = users.id) AS syori')
            ->selectRaw('(SELECT count(exams.id) FROM exams WHERE ended_at IS NOT NULL AND  deleted_at IS NULL AND exams.customer_id = users.id ) AS ended')
            ->leftJoin('exams', function ($join) {
                $join->on('exams.customer_id', '=', 'users.id')
                    ->whereNull('exams.deleted_at');
            })
            ->where([
            "users.type" => "customer",
            "users.partner_id" => $partner_id
            ,"users.admin_id" => $id
            ,"users.deleted_at" => null
            ])
            ->groupBy('users.id');
            $result = User::fromSub($subQuery, 'A')
            ->selectRaw('A.* , (A.count - A.ended) as zan')
            ->selectRaw('A.*')
            ->get();
            return response($result, 201);
        } catch (\Exception $e) {
            return response([], 401);
        }
    }

    public function getPartnerid(Request $request)
    {
        try {
            $result = User::select('partner_id')
            //->where("type",$request->type)
            ->where("id", $request->id)
            ->where("deleted_at", null)
            ->first();
            return response($result->partner_id, 200);
        } catch (\Exception $e) {
            return response(0, 201);
        }
    }
    public function getLisencesList(Request $request)
    {
        try {
            $loginUser = auth()->user()->currentAccessToken();

            $result = DB::table('exams as e')
                ->leftJoin('testparts as t', 'e.test_id', '=', 't.test_id')
                ->leftJoin('tests as ts', 't.test_id', '=', 'ts.id')
                ->leftJoin('examfins as ef', function ($join) {
                    $join->on('ef.exam_id', '=', 'e.id')
                        ->on('ef.testparts_id', '=', 't.id');
                })
                ->leftJoin('userlisences as u', function ($join) use ($request) {
                    $join->on(DB::raw('REPLACE(u.code, "-", "")'), '=', 't.code')
                        ->where('u.user_id', $request->user_id);
                })
                ->where('e.partner_id', $request->user_id)
                ->select(
                    't.code',
                    'u.num',
                    DB::raw('COUNT(CASE WHEN e.started_at IS NOT NULL AND t.status = 1 THEN 1 END) as started_exam_count'),
                    DB::raw('COUNT(CASE WHEN ef.status = 1 THEN 1 END) as ended_exam_count'),
                    DB::raw('SUM(DISTINCT CASE WHEN t.status = 1 THEN ts.testcount ELSE 0 END) as exam_count')
                )
                ->groupBy('t.code', 'u.num')
                ->get();

            return response($result, 200);
        } catch (\Exception $e) {
            return response([], 201);
        }
    }
    public function getUserLisence(Request $request)
    {
        $license = $this->getLicenseListsJP();
        $user_id = $request->user_id;
        // $loginUser = auth()->user()->currentAccessToken();
        if (!$this->checkuser($user_id)) {
            //throw new Exception();
            return response("", 201);
        }

        // $admin = $loginUser->tokenable;
        // var_dump($admin->id);
        $customer = User::find($user_id);
        $partner = User::where(
            [
                "admin_id" => $this->admin_id,
                "id" => $customer->partner_id,
                "deleted_at" => null
            ]
        )->first();
        $result = userlisence::where("user_id", $partner->id)->orderby("code")->get();
        foreach ($result as $k => $value) {
            $result[ $k ][ 'jp' ] = $license[$value[ 'code' ]];
        }
        return response($result, 200);
    }
    public function getUserLisenceCalc(Request $request)
    {
        $license = $this->getLicenseListsJP();
        $customer_id = $request->customer_id;

        $result = userlisence::where("user_id", $customer_id)->orderby("code")->get();
        foreach ($result as $k => $value) {
            $result[ $k ][ 'jp' ] = $license[$value[ 'code' ]];
        }
        return response($result, 200);
    }
    public function getLicenseListsJP()
    {
        $data = [];
        $license = config('const.consts.LISENCE');
        foreach ($license as $value) {
            foreach ($value['list'] as $val) {
                $data[$val['code']] = $val[ 'text' ];
            }
        }
        return $data;
    }
    public function checkEmail(Request $request)
    {
        $email = $request['email'];
        $user = User::where('email', $email)->first();
        if ($user) {
            // すでにメールが登録されている
            return response(true, 200);
        } else {
            return response(true, 201);
        }
    }
    public function checkLoginID(Request $request)
    {
        $login_id = $request['loginid'];
        $editid = $request['editid'];
        try {
            $user = User::where('login_id', $login_id);
            if ($editid > 0) {
                $user = $user->where('id', '!=', $editid);
            }
            $user = $user->first();

            if ($user) {
                // すでにメールが登録されている
                return response("success", 200);
            } else {
                return response("error", 200);

            }
        } catch (Exception $e) {
            return response("error", 200);
        }
    }
    public function getUserData(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $admin_id = $loginUser->tokenable->id;
        try {
            $user = User::where('admin_id', $admin_id)->where("id", $request->id)->first();
            $license = userlisence::where("user_id", $user->id)->get();
            $temp = [];
            foreach ($license as $value) {
                $temp[$value->code] = $value->num;
            }

            $user->licenses = $temp;
            if ($user) {
                return response($user, 200);
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            return response("error", 201);
        }
    }
    public function getUserElement(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        $loginUser = auth()->user()->currentAccessToken();
        $admin_id = $loginUser->tokenable->id;
        $partner_id = User::find($user_id)->partner_id;
        $partner = User::where("id", $partner_id)->where("admin_id", $admin_id)->first();
        return response($partner, 200);
    }
    public function getCustomerEdit(Request $request)
    {
        $partner_id = $request->partner_id;
        $edit_id = $request->edit_id;
        if (!$this->checkuser($partner_id)) {
            throw new Exception();
        }
        try {
            $result = User::Where([
                "id" => $partner_id,
                "partner_id" => $edit_id
            ])
            ->select([
                'name',
                'login_id',
                'post_code',
                'pref',
                'address1',
                'address2',
                'tel',
                'fax',
                'trendFlag',
                'csvFlag',
                'pdfFlag',
                'weightFlag',
                'excelFlag',
                'customFlag',
                'sslFlag',
                'logoImagePath',
                'privacy',
                'displayFlag',
                'privacyText',
                'tanto_name',
                'tanto_address',
                'tanto_busyo',
                'tanto_tel1',
                'tanto_tel2',
                'tanto_name2',
                'tanto_address2'
            ])
            ->first();
            return response($result, 200);
        } catch (Exception $e) {
            return response($e, 400);
        }
    }
    public function customerEdit(Request $request)
    {
        Log::info('顧客情報編集開始'.$request);
        //        $loginUser = auth()->user()->currentAccessToken();
        //        $admin_id = $loginUser->tokenable->id;
        $partner_id = $request->partner_id;
        $id = $request->id;

        if (!$this->checkuser($partner_id)) {
            throw new Exception();
        }
        try {
            $user = User::where(['id' => $id,'partner_id' => $partner_id,'admin_id' => $this->admin_id]);
            $passwd = config('const.consts.PASSWORD');
            $password = openssl_encrypt($request->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);

            $flg = $user->update([
                'login_id' => $request->login_id,
                'name' => $request->name,
                'post_code' => $request->post_code,
                'pref' => $request->pref,
                'address1' => $request->address1,
                'address2' => $request->address2,
                'tel' => $request->tel,
                'fax' => $request->fax,
                'trendFlag' => $request->trendFlag,
                'csvFlag' => $request->csvFlag,
                'pdfFlag' => $request->pdfFlag,
                'weightFlag' => $request->weightFlag,
                'excelFlag' => $request->excelFlag,
                'customFlag' => $request->customFlag,
                'sslFlag' => $request->sslFlag,
                'logoImagePath' => $request->logoImagePath,
                'privacy' => $request->privacy,
                'privacyText' => $request->privacyText,
                'displayFlag' => $request->customerDisplayFlag,
                'tanto_name' => $request->tanto_name,
                'tanto_address' => $request->tanto_address,
                'tanto_busyo' => $request->tanto_busyo,
                'tanto_tel1' => $request->tanto_tel1,
                'tanto_tel2' => $request->tanto_tel2,
                'tanto_name2' => $request->tanto_name2,
                'tanto_address2' => $request->tanto_address2
            ]);
            if ($request->password) {
                Log::info('顧客情報編集パスワード'.$request);
                $flg = $user->update(["password" => $password]);
            }
            if ($flg) {
                Log::info('顧客情報編集成功');

                return response(true, 200);
            } else {
                Log::info('顧客情報編集失敗');

                return response(false, 401);
            }
        } catch (Exception $e) {
            Log::info('顧客情報編集失敗');
            return response($e, 400);
        }
    }

    public function editUserPdfLogo(Request $request)
    {
        try {
            //$loginUser = auth()->user()->currentAccessToken();
            //$type = $loginUser->tokenable->type;
            $partnerId = $request->partnerId;
            $pdfImagePath = $request->pdfImagePath;
            $user = User::find($partnerId);

            $user->update([
                'pdfImagePath' => $pdfImagePath
            ]);
            return response("success", 200);
        } catch (Exception $e) {
            Log::info('ロゴ編集失敗');
            return response($e, 400);
        }
    }
    public function getUserPdfLogo(Request $request)
    {
        try {
            //$loginUser = auth()->user()->currentAccessToken();
            //$type = $loginUser->tokenable->type;
            $partnerId = $request->partnerId;
            $user = User::find($partnerId);
            return response($user, 200);
        } catch (Exception $e) {
            Log::info('ロゴ取得失敗');
            return response($e, 400);
        }
    }

    public function logout()
    {
        auth('sanctum')->user()->tokens()->delete();
        return response(['message' => 'You have been successfully logged out.'], 200);
    }
    // アクセスしていいユーザIDかどうかの確認
    public function checkUserIDData($user_id)
    {
        $loginUser = auth()->user()->currentAccessToken();
        // 管理者でログインしたとき
        if ($loginUser->tokenable->type === "admin") {
            $admin_id = $loginUser->tokenable->id;
            $result = User::find($user_id)->where("admin_id", $admin_id)->count();
            if ($result < 1) {
                return false;
            }
            return true;
        }
        return false;
    }
}
