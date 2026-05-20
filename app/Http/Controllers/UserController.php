<?php

namespace App\Http\Controllers;

use App\Mail\CustomerAddMail;
use App\Mail\CustomerAddMailPassword;
use App\Models\fileuploads;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userlisence;
use App\Models\AdminPageLog;
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
use App\Services\MailService;
use App\Services\UserService;
use App\Services\PasswordService;

class UserController extends Controller
{
    public const G_ADMIN = "admin";
    public const G_PARTNER = "partner";

    private $mailService;
    private $passwordService;
    private $twoFactorCache;
    public function __construct(
        MailService $mailService,
        PasswordService $passwordService,
    ) {
        $this->mailService = $mailService;
        $this->passwordService = $passwordService;
    }
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
        $two_factor = $request->two_factor;
        $user = User::where('login_id', $request->login_id)->first();
        $token = "";
        if ($this->passwordService->verify($user, $request->password)) {
            // 2段階認証が有効な時
            if ($user[ 'two_factor_enabled' ] && !$two_factor) {
                // コードの生成
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                // 2段階認証コードの保持
                $user->update(['two_factor_secret' => $code]);
                // 2段階認証用コードメール発送
                $this->mailService->twoFacterSend($user, $code);
                return response()->json([
                    'two_factor_required' => $user->two_factor_enabled,
                    'two_factor_token'    => $user->id
                ], 200);
            }
            // 2段階認証のチェック
            if ($user[ 'two_factor_enabled' ] && $two_factor) {
                if ($user['two_factor_secret'] != $two_factor) {

                    Log::warning('2段階認証失敗', [
                        'login_id' => $request->login_id,
                        'ip' => $request->ip(),
                    ]);

                    return response(false, 400);
                }
            }
            $token = $user->createToken('my-app-token')->plainTextToken;
            $response = [
                'user' => $user,
                'token' => $token
            ];

            Log::info('ログイン成功', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            return response($response, 200);
        }
        Log::warning('ログイン失敗', [
            'login_id' => $request->login_id,
            'ip' => $request->ip(),
        ]);
        return response("error", 400);

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

    public function editUserDeleteDate(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $params = [];
        $params[ 'partner_id' ] = $request->partner_id;
        $params[ 'id'] = $request->id;
        $params[ 'admin_id' ] = $loginUser->tokenable->id;
        $user = User::where($params)->firstOrFail();
        $user->deleted_at = now();
        $user->save();

        return response($loginUser, 201);
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

        try {

            $user = User::where('type', $request->type)->get();

            Log::info('企業情報変更データ取得に成功しました', [
                'type' => $request->type,
                'count' => $user->count(),
            ]);

            $response = [
                'user' => $user,
            ];

            return response($response, 200);

        } catch (\Exception $e) {
            Log::error('企業情報変更データ取得に失敗しました', [
                'type' => $request->type,
                'error' => $e->getMessage(),
            ]);

            return response([
                'message' => 'error'
            ], 500);
        }
    }

    public function getPartner(Request $request)
    {

        Log::info('getPartner called', [
            'admin_id' => auth()->id(),
            'type' => $request->type,
            'ip' => $request->ip(),
        ]);
        $start = microtime(true);

        try {
            $this->checkAdmin();

            $time = microtime(true) - $start;

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

            Log::info('getPartner performance', [
                'time' => $time
            ]);

            return response($response, 200);
        } catch (\Exception $e) {
            Log::error('getPartner error', [
                'message' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response([], 400);
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
                Mail::to($request->person_address)
                    ->send(
                        (new EditPartnerEditMail($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );
                Log::info('企業情報変更メール配信担当者1');
            }
            if ($request->person_address2) {
                $mailbody[ 'person' ] = $request->person2;
                Mail::to($request->person_address2)
                    ->send(
                        (new EditPartnerEditMail($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );

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

        try {
            DB::beginTransaction();

            $items = $request->all();
            $updatedCount = 0;

            foreach ($items as $item) {
                User::where('id', $item['id'])->update([
                    'login_id' => $item['login_id'],
                    'person' => $item['person'],
                    'person_address' => $item['person_address'],
                    'two_factor_enabled' => (int) filter_var(
                        $item['two_factor_enabled'],
                        FILTER_VALIDATE_BOOLEAN
                    ),
                ]);

                $updatedCount++;
            }

            DB::commit();

            Log::info('管理者企業情報を更新しました', [
                'updated_count' => $updatedCount,
                'user_ids' => collect($items)->pluck('id')->values(),
                'operator_id' => auth()->id(),
            ]);

            return response([
                'result' => true,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('管理者企業情報の更新に失敗しました', [
                'operator_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response([
                'result' => false,
                'message' => '更新に失敗しました。',
            ], 500);
        }

    }
    public function setUserData(Request $request)
    {
        Log::info('新規パートナー登録開始', [
            'admin_id' => auth()->id(),
            'type' => $request->type,
            'login_id' => $request->login_id,
            'name' => $request->name,
            'ip' => $request->ip(),
        ]);
        $passwd = config('const.consts.PASSWORD');

        DB::beginTransaction();
        try {

            //     $request->validate([
            //         'email' => 'required|unique:users',
            //         //'fax' => 'required',
            //     ]);

            User::insert([
                "admin_id" => auth()->id(),
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
                'two_factor_enabled' => $request['two_factor_enabled'],
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

            Log::info('新規パートナー登録成功', [
                'admin_id' => auth()->id(),
                'user_id' => $user_id,
                'type' => $request->type,
                'login_id' => $request->login_id,
            ]);
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
                Mail::to($request->person_address)
                    ->send(
                        (new SetUserDataMail($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );
                Mail::to($request->person_address)->send(
                    (new SetUserDataMailPassword($mailbody))
                    ->from(config('mail.from.address'), config('mail.from.name'))
                );
                Log::info('新規パートナー登録メール送信', [
                    'user_id' => $user_id,
                    'to' => $request->person_address,
                    'target' => '担当者1',
                ]);
            }
            if ($request->person_address2) {
                $mailbody[ 'person' ] = $request->person2;
                $mailbody[ 'password' ] = $request['password'];
                Mail::to($request->person_address2)
                    ->send(
                        (new SetUserDataMail($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );
                Mail::to($request->person_address2)->send(
                    (new SetUserDataMailPassword($mailbody))
                    ->from(config('mail.from.address'), config('mail.from.name'))
                );

                Log::info('新規パートナー登録メール送信', [
                    'user_id' => $user_id,
                    'to' => $request->person_address2,
                    'target' => '担当者2',
                ]);
            }
            DB::commit();
            return response("success", 200);
        } catch (\Exception $exception) {
            Log::error('新規パートナー登録失敗', [
                'admin_id' => auth()->id(),
                'type' => $request->type,
                'login_id' => $request->login_id,
                'message' => $exception->getMessage(),
            ]);
            DB::rollback();
            throw $exception;
        }
    }
    public function setLicensed($data, $user_id)
    {
        // Log::info('ライセンス登録関数の実施:user_id:'.$user_id.":".$data);
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
        Log::info('パートナー更新開始', [
            'admin_id' => auth()->id(),
            'target_user_id' => $request->id,
            'ip' => $request->ip(),
        ]);
        DB::beginTransaction();
        try {
            // $loginUser = auth()->user()->currentAccessToken();
            // $admin_id = $loginUser->tokenable->id;
            $admin_id = auth()->id();
            $user = User::where("id", $request->id)
                ->where("admin_id", $admin_id)
                ->where("type", $request->type)
                ->firstOrFail();
            $data = $request->only([
                'name',
                'login_id',
                'post_code',
                'pref',
                'address1',
                'address2',
                'tel',
                'fax',
                'requestFlag',
                'two_factor_enabled',
                'person',
                'person_address',
                'person2',
                'person_address2',
                'person_tel',
                'system_name',
                'element1','element2','element3','element4','element5',
                'element6','element7','element8','element9','element10',
                'element11','element12','element13','element14',
            ]);

            // パスワードは明示的に処理
            if ($request->filled('password')) {
                $passwd = config('const.consts.PASSWORD');
                $data['password'] = openssl_encrypt(
                    $request->password,
                    'aes-256-cbc',
                    $passwd['key'],
                    0,
                    $passwd['iv']
                );

            }
            $user->update($data);
            $changed = $user->getChanges();
            unset($changed['password']);
            Log::info('パートナー更新成功', [
                'admin_id' => $admin_id,
                'target_user_id' => $user->id,
                'changed' => $changed,
            ]);

            $this->setLicensed($request, $user->id);

            // メール送信（変更があった場合のみ）
            //if ($user->wasChanged(['person_address', 'person_address2'])) {
            // 情報登録メール
            $mailbody = [];
            $mailbody[ 'title' ] = "【Welcome-k】 企業情報更新のお知らせ";
            $mailbody[ 'name' ] = $request->name;
            $mailbody['systemname'] = $request->system_name;
            $licenses = $request->input('licensesBody', []);
            $mailbody[ 'licensesBody' ] = is_array($licenses) ? array_sum($licenses) : 0;
            if ($request->person_address) {

                Log::info('パートナー更新メール送信', [
                    'admin_id' => $admin_id,
                    'target_user_id' => $user->id,
                    'to' => $request->person_address,
                ]);
                $mailbody[ 'person' ] = $request->person;
                Mail::to($request->person_address)
                    ->send(
                        (new EditUserDataMail($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );
            }
            if ($request->person_address2) {
                Log::info('パートナー更新メール送信', [
                    'admin_id' => $admin_id,
                    'target_user_id' => $user->id,
                    'to' => $request->person_address2,
                ]);
                $mailbody[ 'person' ] = $request->person2;
                Mail::to($request->person_address2)
                    ->send(
                        (new EditUserDataMail($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );
            }
            //}
            DB::commit();
            return response("success", 200);
        } catch (\Exception $exception) {

            Log::error('パートナー更新エラー', [
                'admin_id' => auth()->id(),
                'target_user_id' => $request->id,
                'message' => $exception->getMessage(),
            ]);

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
                'two_factor_enabled' => $request['two_factor_enabled'],
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
                Mail::to($request['tanto_address'])->send(
                    (new CustomerAddMail($mailbody))
                    ->from(config('mail.from.address'), config('mail.from.name'))
                );

                Mail::to($request['tanto_address'])->send(
                    (new CustomerAddMailPassword($mailbody))
                    ->from(config('mail.from.address'), config('mail.from.name'))
                );

                Log::info('新規顧客登録メール配信担当者1');
            }
            if ($request['tanto_address2']) {
                $mailbody[ 'person' ] = $request['tanto_name2'];
                $mailbody[ 'password' ] = $request['password'];
                Mail::to($request['tanto_address2'])
                    ->send(
                        (new CustomerAddMail($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );

                Mail::to($request['tanto_address2'])
                    ->send(
                        (new CustomerAddMailPassword($mailbody))
                        ->from(config('mail.from.address'), config('mail.from.name'))
                    );
                Log::info('新規顧客登録メール配信担当者2');
            }

            Log::info('新規顧客登録成功');
            DB::commit();
            return response("success", 200);
        } catch (\Exception $e) {
            Log::info('新規顧客登録失敗'.$e);

            return response($e, 400);
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
            // リクエスト値を検証
            $request->validate([
                'partner_id' => ['required', 'integer'],
            ]);

            // ログインユーザーを取得
            $loginUser = auth()->user();

            // 未ログインの場合は401を返す
            if (!$loginUser) {
                return response([], 401);
            }

            // 初期値を設定
            $adminId = $loginUser->id;
            $partnerId = (int) $request->partner_id;

            // 顧客一覧取得の開始ログ
            Log::info('顧客一覧取得 開始', [
                'login_user_id' => $loginUser->id,
                'login_user_type' => $loginUser->type,
                'request_partner_id' => $request->partner_id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // パートナー権限でログインした場合
            if ($loginUser->type === 'partner') {
                // パートナーに紐づく管理者IDを設定
                $adminId = $loginUser->admin_id;

                // ログイン中のパートナーIDを設定
                $partnerId = $loginUser->id;

                // URLパラメータのpartner_idとログイン中のpartner_idが違う場合は403を返す
                if ((int) $partnerId !== (int) $request->partner_id) {
                    Log::warning('顧客一覧取得 権限不一致', [
                        'login_user_id' => $loginUser->id,
                        'login_user_type' => $loginUser->type,
                        'login_partner_id' => $partnerId,
                        'request_partner_id' => $request->partner_id,
                        'ip' => $request->ip(),
                    ]);

                    return response([], 403);
                }
            }

            $subQuery = User::select('users.*')
                ->selectRaw('count(exams.id) AS count')
                ->selectRaw('
                (
                    SELECT count(exams.id)
                    FROM exams
                    WHERE started_at IS NOT NULL
                    AND deleted_at IS NULL
                    AND exams.customer_id = users.id
                ) AS syori
            ')
                ->selectRaw('
                (
                    SELECT count(exams.id)
                    FROM exams
                    WHERE ended_at IS NOT NULL
                    AND deleted_at IS NULL
                    AND exams.customer_id = users.id
                ) AS ended
            ')
                ->leftJoin('exams', function ($join) {
                    $join->on('exams.customer_id', '=', 'users.id')
                        ->whereNull('exams.deleted_at');
                })
                ->where([
                    'users.type' => 'customer',
                    'users.partner_id' => $partnerId,
                    'users.admin_id' => $adminId,
                    'users.deleted_at' => null,
                ])
                ->groupBy('users.id');

            $result = User::fromSub($subQuery, 'A')
                ->selectRaw('A.*, (A.count - A.ended) as zan')
                ->get();

            // 顧客一覧取得の成功ログ
            Log::info('顧客一覧取得 成功', [
                'login_user_id' => $loginUser->id,
                'login_user_type' => $loginUser->type,
                'partner_id' => $partnerId,
                'admin_id' => $adminId,
                'count' => $result->count(),
            ]);

            return response($result, 200);

        } catch (\Exception $e) {
            // 顧客一覧取得の失敗ログ
            Log::error('顧客一覧取得 失敗', [
                'login_user_id' => auth()->id(),
                'request_partner_id' => $request->partner_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
            ]);

            return response([], 500);
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
            // ログインユーザー情報を取得
            $loginUser = auth()->user();

            // 検索対象のユーザーIDを取得
            $targetUserId = $request->user_id;

            // API実行ログを出力
            Log::info('ライセンス一覧取得 開始', [
                'login_user_id' => $loginUser?->id,
                'target_user_id' => $targetUserId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

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

            // API成功ログを出力
            Log::info('ライセンス一覧取得 成功', [
                'login_user_id' => $loginUser?->id,
                'target_user_id' => $targetUserId,
                'count' => $result->count(),
            ]);

            return response($result, 200);
        } catch (\Exception $e) {
            // エラーログを出力
            Log::error('ライセンス一覧取得 失敗', [
                'login_user_id' => auth()->id(),
                'target_user_id' => $request->user_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response([], 500);
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
        $loginUser = $request->user()->currentAccessToken();
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
                'two_factor_enabled',
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
        Log::info('顧客情報編集開始', [
            'user_id' => $request->id,
            'partner_id' => $request->partner_id,
        ]);
        $partner_id = $request->partner_id;
        $id = $request->id;

        if (!$this->checkuser($partner_id)) {
            throw new Exception();
        }
        try {

            $user = User::where([
                    'id' => $id,
                    'partner_id' => $partner_id,
                    'admin_id' => $this->admin_id
                ])->first();
            if (!$user) {
                Log::info('顧客情報編集対象なし');
                return response(false, 404);
            }
            $passwd = config('const.consts.PASSWORD');
            $password = openssl_encrypt($request->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);

            $user->update([
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
                'two_factor_enabled' => $request->two_factor_enabled,
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
                Log::info('パスワード更新あり', [
                    'user_id' => $id
                ]);
                $user->update(["password" => $password]);
            }

            Log::info('顧客情報編集成功');

            return response(true, 200);

        } catch (Exception $e) {
            Log::error('顧客情報編集失敗', [
                'user_id' => $id ?? null,
                'partner_id' => $partner_id ?? null,
                'input' => $request->except(['password']),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
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
