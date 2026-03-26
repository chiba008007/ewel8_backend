<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\exampfs;
use App\Models\Test;
use App\Models\testparts;
use Illuminate\Support\Facades\DB;
use App\Models\examfins;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ExamLoginHistory;
use App\Models\Examvfj;
use App\Services\ExamAuthService;
use App\Services\ExamProfileService;
use App\Services\TestExamMenuService;
use App\Models\Popular;

class ExamVFJController extends Controller
{
    /**
     * 検査ログイン
     */
    public function index(Request $request, ExamAuthService $service)
    {
        try {
            $response = $service->authenticate($request);
            return response($response, 200);
        } catch (\Throwable $e) {
            return response('error', 400);
        }
    }

    public function checkStatus(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $exam_id = $loginUser->tokenable->id;
        $testparts_id = $request->testparts_id;

        $last = examfins::Where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();
        if ($last && $last->status == 1) {
            return response(true, 200);
        } else {
            return response(false, 200);
        }
    }

    public function getExam(Request $request)
    {
        $now = date("Y-m-d H:i:s");
        try {
            $rlt = Test::select(["users.company_name","tests.*"])
            ->where("params", $request->params)
            ->where("status", 1)
            ->where("startdaytime", "<=", $now)
            ->where("enddaytime", ">=", $now)
            ->leftjoin("users", "users.id", "=", "tests.user_id")
            ->first();
            if ($rlt) {
                return response($rlt, 200);
            } else {
                return response([], 201);
            }
        } catch (Exception $e) {
            return response([], 201);
        }
    }

    public function getExamData()
    {
        try {
            $loginUser = auth()->user()->currentAccessToken();
            $passwd = config('const.consts.PASSWORD');
            $name = explode("　", $loginUser->tokenable->name);
            $kana = explode("　", $loginUser->tokenable->kana);
            $gender = $loginUser->tokenable->gender;
            $pwd = openssl_decrypt($loginUser->tokenable->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
            $loginUser->password = $pwd;

            $loginUser->name1 = (isset($name[0])) ? $name[0] : "";
            $loginUser->name2 = (isset($name[1])) ? $name[1] : "";
            $loginUser->kana1 = (isset($kana[0])) ? $kana[0] : "";
            $loginUser->kana2 = (isset($kana[1])) ? $kana[1] : "";
            $loginUser->gender = $gender;
            if (!$loginUser) {
                return response([], 200);
            }
            return response($loginUser, 200);
        } catch (Exception $e) {
            return response(false, 200);
        }

    }

    /**
     * 個人情報の編集
     */
    public function editExamData(Request $request, ExamProfileService $service)
    {
        // 認証済みユーザー（トークンの所有者）
        /** @var \App\Models\Exam $user */
        $user = auth()->user();
        //$token = $user->currentAccessToken();

        // 更新対象のパラメータ
        $params = [
            'name'   => $request->name,
            'kana'   => $request->kana,
            'gender' => $request->gender,
        ];

        try {
            // 業務ロジックは Service に委譲
            $service->updateProfile(
                $user->id,
                $user->email,
                $request->k,
                $params
            );

            return response(true, 200);
        } catch (\Throwable $e) {
            // 失敗時は詳細を返さない
            return response(false, 400);
        }
    }

    /**
     * 試験メニュー一覧取得 API
     *
     * - 認証済み試験ユーザーを前提とする
     * - params に紐づくテスト構成を返却する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTestExamMenu(
        Request $request,
        TestExamMenuService $service
    ) {
        /** @var \App\Models\Exam $exam */
        $exam = auth()->user();
        //$token = $user->currentAccessToken();
        try {
            $result = $service->getMenuForExam(
                $exam->id,
                $request->params
            );

            return response($result, 200);
        } catch (\Throwable $e) {
            // 取得失敗時は空配列を返却
            return response([], 400);
        }
    }
    public function getTestDataExam(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $examid = $loginUser->tokenable->id;
        $customer_id = $loginUser->tokenable->customer_id;
        $partner_id = $loginUser->tokenable->partner_id;
        $params = $request->params;
        $result = [];
        try {
            $result = Test::select(
                "tests.*"
            )
            ->where([
                "params" => $params,
                "customer_id" => $customer_id,
                "partner_id" => $partner_id,
            ])
            ->first();
        } catch (Exception $e) {
            return response([], 201);
        }

        return response($result, 200);
    }

    public function getVFJ(Request $request)
    {
        $loginUser = auth()->user();
        $exam_id = $loginUser->tokenable->id;
        $testparts_id = $request->testparts_id;
        // 最後の1件を取得
        $last = Examvfj::select("*")->latest("id")->where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();
        // 結果データがあるときは結果をまとめて取得
        if ($last->endtime) {
            $ans_data = config('const.PFS3.PFS3');
            $last->result = $ans_data[$last->soyo];
        }
        return response($last, 200);
    }
    public function setVFJ(Request $request)
    {
        $loginUser = auth()->user();
        $exam_id = $loginUser->id;

        // 既存のテストステータスを0にする
        Examvfj::where("exam_id", "=", $exam_id)
        ->where("status", "=", 1)
        ->update(['status' => 0]);

        $params = [
            'testparts_id' => $request->testparts_id,
            'exam_id'      => $exam_id,
            'status'       => 1,
            'starttime'    => now(),
        ];

        try {
            if (Examvfj::create($params)) {
                return response("success", 200);
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            return response("error", 400);
        }
    }
    public function editVFJ(Request $request)
    {

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $token = $user->currentAccessToken();
        $exam_id = $token->tokenable->id;

        $testparts_id = $request->testparts_id;
        Log::info('VFJ検査回答登録');
        Log::info('ページ数:'.$request->page);
        Log::info('受検者id:'.$exam_id);
        Log::info('testparts_id:'.$testparts_id);
        // 最後の1件を取得
        $last = Examvfj::select("id")->latest("id")->where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();
        $exam = Examvfj::find($last[ 'id' ]);

        if ($request->page == 2) {
            $exam->starttime = date("Y-m-d H:i:s");
        }

        $selectPoint = $request->selectPoint;
        foreach ($selectPoint as $key => $value) {
            $q = "q".$key;
            $exam[$q] = $value;
        }
        $exam->save();

        // 最後のページ
        if ($request->page == 8) {
            try {
                // 重み計算
                $weight = $this->getResultVFJWeight($testparts_id);
                //重みの算定・平均・標準偏差の取得
                $avg = $this->getAVG($weight);

                $updateData = [];

                // w1〜w12
                foreach ($weight as $i => $val) {
                    $updateData['w'.$i] = $val;
                }
                // dev1〜dev12（ここが重要）
                foreach ($weight as $key => $val) {
                    $updateData["dev".$key] = isset($avg['top6'][$key]) ? $val : 0;
                }

                // avg / std
                $updateData['avg'] = $avg['avg'];
                $updateData['std'] = $avg['std'];
                $updateData['endtime'] = date("Y-m-d H:i:s");
                // 更新
                $exam->update($updateData);

                examfins::complete($exam_id, $testparts_id);

                // 最終登録データ確認
                exam::setEndTime();
                // メール配信受検者残数
                exam::sendRemainMail($request);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 409); // Conflict
            }

        }
        return response("success", 200);
    }
    public function downloadExam()
    {
        $loginUser = auth()->user();
        $id = $loginUser->tokenable->id;
        $code = $loginUser->tokenable->email;
        $password = $loginUser->tokenable->password;
        $passwd = config('const.consts.PASSWORD');
        $decript = openssl_decrypt($password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
        $decript = preg_replace("/\//", "-", $decript);
        //header("Location:/pdf/".$id."/code/".$code."/birth/".$decript);
        $params = [];
        $params[ 'id' ] = $id;
        $params[ 'code' ] = $code;
        $params[ 'decript' ] = $decript;
        return response($params, 200);
        exit();

    }
    public function getResultVFJWeight($testparts_id)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $token = $user->currentAccessToken();
        $exam_id = $token->tokenable->id;
        $last = examVFJ::select("*")->latest("id")->where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();

        $CODE1 = array(1,10,8,1,5,1,7,2,6,1,3,2,7,2,5,1,5,1,2,1,2,3,4,2,3,7,3,5,2,1,3,5,3,4,3,4,8,4,9,1,6,2,4,5,6,1,6,4,6,3,4,6,7,8,4,8,3,9,2,1,5,9,2,10,7,11);
        $CODE2 = array(4,12,11,5,8,9,12,3,11,12,7,4,10,5,12,8,7,6,9,7,11,5,6,10,6,11,8,6,8,10,9,11,10,9,11,5,9,7,11,2,7,12,8,10,8,3,9,12,10,12,10,12,8,10,11,12,4,10,6,11,9,12,7,11,9,12);

        $ans = [];
        for ($i = 1; $i <= 66; $i++) {
            $ans["vf" . $i] = $last->{"q" . $i};
        }

        $i = 1;
        $n = 0;

        foreach ($ans as $key => $val) {
            if (preg_match("/vf/", $key)) {

                $vkey = "vf" . $i;
                $a = $ans[$vkey];

                switch ($a) {
                    case "1":
                        $adata[$CODE1[$n]][$CODE2[$n]] = 5;
                        $adata[$CODE2[$n]][$CODE1[$n]] = 0.2;
                        break;
                    case "2":
                        $adata[$CODE1[$n]][$CODE2[$n]] = 3;
                        $adata[$CODE2[$n]][$CODE1[$n]] = 0.333333333333333;
                        break;
                    case "3":
                        $adata[$CODE1[$n]][$CODE2[$n]] = 0.333333333333333;
                        $adata[$CODE2[$n]][$CODE1[$n]] = 3;
                        break;
                    case "4":
                        $adata[$CODE1[$n]][$CODE2[$n]] = 0.2;
                        $adata[$CODE2[$n]][$CODE1[$n]] = 5;
                        break;
                }

                $i++;
                $n++;
            }
        }

        ksort($adata);

        $num = 1;
        foreach ($adata as $key => $val) {
            $kika = 0;
            foreach ($val as $k => $v) {
                $kika += log($v);
            }
            $ahp[$num] = exp($kika / 12);
            $num++;
        }

        $w = 0;
        foreach ($ahp as $val) {
            $w += $val;
        }

        for ($i = 1; $i <= 12; $i++) {
            $weight[$i] = round($ahp[$i] / $w, 3);
        }

        return $weight;
    }

    public function getAVG($weight)
    {
        //重み付けの上位6件を取得
        //キーを維持して値で逆順ソート
        arsort($weight);
        //配列の先頭から6個分を取り出す
        $top6 = array_slice($weight, 0, 6, true);

        $list = $this->getPublicSetData($top6);
        $list['top6'] = $top6;
        return $list;
    }

    public function getPublicSetData($weight)
    {
        // データ取得
        $rows = Popular::all();

        $list = [];

        foreach ($rows as $row) {
            $sum = 0;

            foreach ($weight as $key => $val) {
                $sum += $row->{"dev".$key} * $val;
            }

            $list[] = $sum;
        }

        // データなしガード
        if (empty($list)) {
            return [
                'avg' => 0,
                'std' => 0
            ];
        }

        $avg = round($this->average($list), 2);
        $std = round($this->standard_deviation($list), 4);

        return [
            'avg' => $avg,
            'std' => $std
        ];
    }
    public static function average(array $values)
    {
        return (float) (array_sum($values) / count($values));
    }
    public static function variance(array $values)
    {
        $ave = self::average($values);

        $variance = 0.0;
        foreach ($values as $val) {
            $variance += pow($val - $ave, 2);
        }

        return (float) ($variance / (count($values) - 1));
    }

    public static function standard_deviation(array $values)
    {
        $variance = self::variance($values);

        return (float) sqrt($variance);
    }

    // public function test()
    // {
    //     $loginUser = auth()->user()->currentAccessToken();
    //     return response($loginUser, 200);
    // }
}
