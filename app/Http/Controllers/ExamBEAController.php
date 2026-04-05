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
use App\Models\Exambea;
use App\Services\ExamAuthService;
use App\Services\ExamProfileService;
use App\Services\TestExamMenuService;
use App\Models\Popular;

class ExamBEAController extends Controller
{
    public const CODE = "BEA";
    public $array_dp_lv = [];
    public $array_dp = [];
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
        $loginUser = auth()->user();
        $exam_id = $loginUser->id;
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
    /*
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
*/

    public function getBEA(Request $request)
    {
        $loginUser = auth()->user();
        $exam_id = $loginUser->id;
        $testparts_id = $request->testparts_id;
        // 最後の1件を取得
        $last = Exambea::select("*")->latest("id")->where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();
        // 結果データがあるときは結果をまとめて取得
        // if ($last->endtime) {
        //     $ans_data = config('const.PFS3.PFS3');
        //     $last->result = $ans_data[$last->soyo];
        // }
        return response($last, 200);
    }
    public function setBEA(Request $request)
    {
        $loginUser = auth()->user();
        $exam_id = $loginUser->id;
        // テスト時間の取得
        $timelimit = testparts::where('test_id', $loginUser->test_id)
            ->where('code', self::CODE)
            ->value('timelimit');

        // 既存のテストステータスを0にする
        Exambea::where("exam_id", "=", $exam_id)
        ->where("status", "=", 1)
        ->update(['status' => 0]);

        $params = [
            'testparts_id' => $request->testparts_id,
            'exam_id'      => $exam_id,
            'status'       => 1,
            'starttime'    => now(),
            'limittime'    => now()->addMinutes($timelimit),
        ];

        try {
            if (Exambea::create($params)) {
                return response("success", 200);
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            return response("error", 400);
        }
    }
    public function editBEA(Request $request)
    {

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $token = $user->currentAccessToken();
        $exam_id = $token->tokenable->id;

        $testparts_id = $request->testparts_id;
        Log::info('BEA検査回答登録');
        Log::info('ページ数:'.$request->page);
        Log::info('受検者id:'.$exam_id);
        Log::info('testparts_id:'.$testparts_id);
        // 最後の1件を取得
        $last = Exambea::select("id")->latest("id")->where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();
        $exam = Exambea::find($last[ 'id' ]);

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
        if ($request->page == 12) {
            $this->getArrayPoint();

            try {
                // 回答データの取得
                $exam = Exambea::find($last[ 'id' ]);
                $array_result = $this->getEAS($exam);
                $score = [];
                for ($i = 1; $i <= 106; $i++) {
                    $score[$i] = $exam["q".$i];
                }
                $array_result = $this->getEAS($score);

                $updateData = [];
                $updateData[ 'sougo'  ] = $array_result[ 'sougo' ];
                $updateData['yomitori'] = $array_result[ 'yomitori' ];
                $updateData['rikai' ] = $array_result[ 'rikai'    ];
                $updateData['sentaku' ] = $array_result[ 'sentaku'  ];
                $updateData['kirikae' ] = $array_result[ 'kirikae'  ];
                $updateData['jyoho'] = $array_result[ 'jyoho'    ];
                $updateData['endtime'] = now();
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

    public function getArrayPoint()
    {
        $this->array_dp_lv = [
            "sougo" => ["ave" => 44.63546,"hen" => 6.781757]
            ,"read" => ["ave" => 11.06559,"hen" => 2.609536]
            ,"change" => ["ave" => 11.24206,"hen" => 2.33245]
            ,"understand" => ["ave" => 11.22123,"hen" => 2.00448]
            ,"select" => ["ave" => 11.10658,"hen" => 1.943967]
            ,"vaias" => ["ave" => 1.7,"hen" => 0.87]
        ];
        $this->array_dp = [
            "1" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "2" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "3" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "4" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "5" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "6" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "7" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "8" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "9" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "10" => [
                    1 => 0.594501718
                    ,2 => 0.237113402
                    ,3 => 0.096219931
                    ,4 => 0.068728522
                    ,5 => 0.003436426
                    ,6 => 0
                    ,7 => 0
                ],
            "11" => [
                    1 => 0.006872852
                    ,2 => 0.048109966
                    ,3 => 0.079037801
                    ,4 => 0.419243986
                    ,5 => 0.446735395
                    ,6 => 0
                    ,7 => 0
                ],
            "12" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "13" => [
                    1 => 0.419243986
                    ,2 => 0.329896907
                    ,3 => 0.175257732
                    ,4 => 0.072164948
                    ,5 => 0.003436426
                    ,6 => 0
                    ,7 => 0
                ],
            "14" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "15" => [
                    1 => 0.457044674
                    ,2 => 0.29209622
                    ,3 => 0.151202749
                    ,4 => 0.099656357
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "16" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "17" => [
                    1 => 0.621993127
                    ,2 => 0.219931271
                    ,3 => 0.099656357
                    ,4 => 0.04467354
                    ,5 => 0.013745704
                    ,6 => 0
                    ,7 => 0
                ],
            "18" => [
                    1 => 0.783505155
                    ,2 => 0.151202749
                    ,3 => 0.048109966
                    ,4 => 0.013745704
                    ,5 => 0.003436426
                    ,6 => 0
                    ,7 => 0
                ],
            "19" => [
                    1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "20" => [
                        1 => 0.51890
                    ,2 => 0.30928
                    ,3 => 0.13402
                    ,4 => 0.03093
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "21" => [
                        1 => 0.54639
                    ,2 => 0.33333
                    ,3 => 0.09622
                    ,4 => 0.01375
                    ,5 => 0.01031
                    ,6 => 0
                    ,7 => 0
                ],
            "22" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "23" => [
                        1 => 0.57045
                    ,2 => 0.23024
                    ,3 => 0.15120
                    ,4 => 0.04811
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "24" => [
                        1 => 0.51546
                    ,2 => 0.20962
                    ,3 => 0.18213
                    ,4 => 0.08247
                    ,5 => 0.01031
                    ,6 => 0
                    ,7 => 0
                ],
            "25" => [
                        1 => 0.49828
                    ,2 => 0.30241
                    ,3 => 0.13402
                    ,4 => 0.06186
                    ,5 => 0.00344
                    ,6 => 0
                    ,7 => 0
                ],
            "26" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "27" => [
                        1 => 0.35395
                    ,2 => 0.26460
                    ,3 => 0.22337
                    ,4 => 0.14089
                    ,5 => 0.01718
                    ,6 => 0
                    ,7 => 0
                ],
            "28" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "29" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "30" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "31" => [
                        1 => 0.01031
                    ,2 => 0.00687
                    ,3 => 0.01031
                    ,4 => 0.05155
                    ,5 => 0.92096
                    ,6 => 0
                    ,7 => 0
                ],
            "32" => [
                        1 => 0.54639
                    ,2 => 0.17182
                    ,3 => 0.12715
                    ,4 => 0.08935
                    ,5 => 0.06529
                    ,6 => 0
                    ,7 => 0
                ],
            "33" => [
                        1 => 0.52234
                    ,2 => 0.20275
                    ,3 => 0.15120
                    ,4 => 0.07904
                    ,5 => 0.04467
                    ,6 => 0
                    ,7 => 0
                ],
            "34" => [
                        1 => 0.50515
                    ,2 => 0.19588
                    ,3 => 0.17182
                    ,4 => 0.08591
                    ,5 => 0.04124
                    ,6 => 0
                    ,7 => 0
                ],
            "35" => [
                        1 => 0.00687
                    ,2 => 0.01375
                    ,3 => 0.02749
                    ,4 => 0.05498
                    ,5 => 0.89691
                    ,6 => 0
                    ,7 => 0
                ],
            "36" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "37" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "38" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "39" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "40" => [
                        1 => 0.49828
                    ,2 => 0.24399
                    ,3 => 0.16838
                    ,4 => 0.06529
                    ,5 => 0.02405
                    ,6 => 0
                    ,7 => 0
                ],
            "41" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "42" => [
                        1 => 0.61856
                    ,2 => 0.18557
                    ,3 => 0.10653
                    ,4 => 0.05498
                    ,5 => 0.03436
                    ,6 => 0
                    ,7 => 0
                ],
            "43" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "44" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "45" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "46" => [
                        1 => 0.56357
                    ,2 => 0.24742
                    ,3 => 0.10653
                    ,4 => 0.06186
                    ,5 => 0.02062
                    ,6 => 0
                    ,7 => 0
                ],
            "47" => [
                        1 => 0.00344
                    ,2 => 0.01718
                    ,3 => 0.02062
                    ,4 => 0.05842
                    ,5 => 0.90034
                    ,6 => 0
                    ,7 => 0
                ],
            "48" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "49" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "50" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "51" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "52" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "53" => [
                        1 => 0.67354
                    ,2 => 0.21306
                    ,3 => 0.06873
                    ,4 => 0.03436
                    ,5 => 0.01031
                    ,6 => 0
                    ,7 => 0
                ],
            "54" => [
                        1 => 0
                    ,2 => 0.01375
                    ,3 => 0.02749
                    ,4 => 0.07560
                    ,5 => 0.88316
                    ,6 => 0
                    ,7 => 0
                ],
            "55" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "56" => [
                        1 => 0.37457
                    ,2 => 0.20962
                    ,3 => 0.17869
                    ,4 => 0.16151
                    ,5 => 0.07560
                    ,6 => 0
                    ,7 => 0
                ],
            "57" => [
                        1 => 0.70790
                    ,2 => 0.14777
                    ,3 => 0.09966
                    ,4 => 0.03780
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "58" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "59" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "60" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "61" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "62" => [
                        1 => 0.01031
                    ,2 => 0.01031
                    ,3 => 0.01375
                    ,4 => 0.94845
                    ,5 => 0.01718
                    ,6 => 0
                    ,7 => 0
                ],
            "63" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "64" => [
                        1 => 0.02062
                    ,2 => 0.03780
                    ,3 => 0.47079
                    ,4 => 0.02749
                    ,5 => 0.44330
                    ,6 => 0
                    ,7 => 0
                ],
            "65" => [
                        1 => 0.09622
                    ,2 => 0.05155
                    ,3 => 0.26804
                    ,4 => 0.08247
                    ,5 => 0.50172
                    ,6 => 0
                    ,7 => 0
                ],
            "66" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "67" => [
                        1 => 0.33677
                    ,2 => 0.02405
                    ,3 => 0.52577
                    ,4 => 0.03780
                    ,5 => 0.07560
                    ,6 => 0
                    ,7 => 0
                ],
            "68" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "69" => [
                        1 => 0.00687
                    ,2 => 0.17182
                    ,3 => 0.02749
                    ,4 => 0.77663
                    ,5 => 0.01718
                    ,6 => 0
                    ,7 => 0
                ],
            "70" => [
                        1 => 0.12715
                    ,2 => 0.19588
                    ,3 => 0.39863
                    ,4 => 0.05842
                    ,5 => 0.21993
                    ,6 => 0
                    ,7 => 0
                ],
            "71" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "72" => [
                        1 => 0.02405
                    ,2 => 0.02749
                    ,3 => 0.90034
                    ,4 => 0.04467
                    ,5 => 0.00344
                    ,6 => 0
                    ,7 => 0
                ],
            "73" => [
                        1 => 0.01718
                    ,2 => 0.92096
                    ,3 => 0.01375
                    ,4 => 0.01375
                    ,5 => 0.03436
                    ,6 => 0
                    ,7 => 0
                ],
            "74" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "75" => [
                        1 => 0.01375
                    ,2 => 0.87285
                    ,3 => 0.08247
                    ,4 => 0.01375
                    ,5 => 0.01718
                    ,6 => 0
                    ,7 => 0
                ],
            "76" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "77" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "78" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "79" => [
                        1 => 0.01375
                    ,2 => 0.08935
                    ,3 => 0.30584
                    ,4 => 0.40893
                    ,5 => 0.18213
                    ,6 => 0
                    ,7 => 0
                ],
            "80" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "81" => [
                        1 => 0.13746
                    ,2 => 0.12715
                    ,3 => 0.16151
                    ,4 => 0.33333
                    ,5 => 0.24055
                    ,6 => 0
                    ,7 => 0
                ],
            "82" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "83" => [
                        1 => 0.04124
                    ,2 => 0.06186
                    ,3 => 0.13746
                    ,4 => 0.27491
                    ,5 => 0.48454
                    ,6 => 0
                    ,7 => 0
                ],
            "84" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "85" => [
                        1 => 0.02749
                    ,2 => 0.08591
                    ,3 => 0.13746
                    ,4 => 0.25773
                    ,5 => 0.49141
                    ,6 => 0
                    ,7 => 0
                ],
            "86" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "87" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "88" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "89" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "90" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "91" => [
                        1 => 0.00687
                    ,2 => 0.01718
                    ,3 => 0.05498
                    ,4 => 0.18900
                    ,5 => 0.73196
                    ,6 => 0
                    ,7 => 0
                ],
            "92" => [
                        1 => 0.02062
                    ,2 => 0.06873
                    ,3 => 0.12715
                    ,4 => 0.27491
                    ,5 => 0.50859
                    ,6 => 0
                    ,7 => 0
                ],
            "93" => [
                        1 => 0.01375
                    ,2 => 0.05155
                    ,3 => 0.09278
                    ,4 => 0.29553
                    ,5 => 0.54639
                    ,6 => 0
                    ,7 => 0
                ],
            "94" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "95" => [
                        1 => 0.03780
                    ,2 => 0.07904
                    ,3 => 0.18900
                    ,4 => 0.30241
                    ,5 => 0.39175
                    ,6 => 0
                    ,7 => 0
                ],
            "96" => [
                        1 => 0.02062
                    ,2 => 0.05155
                    ,3 => 0.18900
                    ,4 => 0.35052
                    ,5 => 0.38832
                    ,6 => 0
                    ,7 => 0
                ],
            "97" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "98" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "99" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "100" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "101" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "102" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "103" => [
                        1 => 0.09622
                    ,2 => 0.13058
                    ,3 => 0.18213
                    ,4 => 0.31959
                    ,5 => 0.27148
                    ,6 => 0
                    ,7 => 0
                ],
            "104" => [
                        1 => 0.07560
                    ,2 => 0.17182
                    ,3 => 0.20619
                    ,4 => 0.28522
                    ,5 => 0.26117
                    ,6 => 0
                    ,7 => 0
                ],
            "105" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "106" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "107" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "108" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "109" => [
                        1 => 0.37457
                    ,2 => 0.32646
                    ,3 => 0.18557
                    ,4 => 0.07560
                    ,5 => 0.03780
                    ,6 => 0
                    ,7 => 0
                ],
            "110" => [
                        1 => 0.02405
                    ,2 => 0.04467
                    ,3 => 0.08935
                    ,4 => 0.26804
                    ,5 => 0.57388
                    ,6 => 0
                    ,7 => 0
                ],
            "111" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "112" => [
                        1 => 0.04124
                    ,2 => 0.05155
                    ,3 => 0.12371
                    ,4 => 0.18557
                    ,5 => 0.59794
                    ,6 => 0
                    ,7 => 0
                ],
            "113" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "114" => [
                        1 => 0.04811
                    ,2 => 0.07560
                    ,3 => 0.22337
                    ,4 => 0.39863
                    ,5 => 0.25430
                    ,6 => 0
                    ,7 => 0
                ],
            "115" => [
                        1 => 0.00344
                    ,2 => 0.01718
                    ,3 => 0.06529
                    ,4 => 0.20962
                    ,5 => 0.70447
                    ,6 => 0
                    ,7 => 0
                ],
            "116" => [
                        1 => 0.41237
                    ,2 => 0.36426
                    ,3 => 0.17526
                    ,4 => 0.03436
                    ,5 => 0.01375
                    ,6 => 0
                    ,7 => 0
                ],
            "117" => [
                        1 => 0.00344
                    ,2 => 0.01031
                    ,3 => 0.06529
                    ,4 => 0.28866
                    ,5 => 0.63230
                    ,6 => 0
                    ,7 => 0
                ],
            "118" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "119" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "120" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "121" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "122" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "123" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "124" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "125" => [
                        1 => 0.73540
                    ,2 => 0.15120
                    ,3 => 0.06529
                    ,4 => 0.04124
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "126" => [
                        1 => 0.49141
                    ,2 => 0.26804
                    ,3 => 0.12715
                    ,4 => 0.07904
                    ,5 => 0.03436
                    ,6 => 0
                    ,7 => 0
                ],
            "127" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "128" => [
                        1 => 0.60481
                    ,2 => 0.25773
                    ,3 => 0.07904
                    ,4 => 0.04811
                    ,5 => 0.01031
                    ,6 => 0
                    ,7 => 0
                ],
            "129" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "130" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "131" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "132" => [
                        1 => 0.66667
                    ,2 => 0.16838
                    ,3 => 0.10309
                    ,4 => 0.04467
                    ,5 => 0.01718
                    ,6 => 0
                    ,7 => 0
                ],
            "133" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "134" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "135" => [
                        1 => 0.56014
                    ,2 => 0.25086
                    ,3 => 0.10653
                    ,4 => 0.05842
                    ,5 => 0.02405
                    ,6 => 0
                    ,7 => 0
                ],
            "136" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "137" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "138" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "139" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "140" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "141" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "142" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "143" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "144" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "145" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "146" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "147" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "148" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "149" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "150" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "151" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "152" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "153" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "154" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "155" => [
                        1 => 0.60825
                    ,2 => 0.25430
                    ,3 => 0.06529
                    ,4 => 0.05842
                    ,5 => 0.01375
                    ,6 => 0
                    ,7 => 0
                ],
            "156" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "157" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "158" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "159" => [
                        1 => 0.54983
                    ,2 => 0.26460
                    ,3 => 0.10997
                    ,4 => 0.05842
                    ,5 => 0.01718
                    ,6 => 0
                    ,7 => 0
                ],
            "160" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],

            "161" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "162" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "163" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "164" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "165" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "166" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "167" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "168" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "169" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "170" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "171" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "172" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "173" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "174" => [
                        1 => 0.66323
                    ,2 => 0.26117
                    ,3 => 0.05842
                    ,4 => 0.01375
                    ,5 => 0.00344
                    ,6 => 0
                    ,7 => 0
                ],
            "175" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "176" => [
                        1 => 0.82818
                    ,2 => 0.13058
                    ,3 => 0.03093
                    ,4 => 0.00687
                    ,5 => 0.00344
                    ,6 => 0
                    ,7 => 0
                ],
            "177" => [
                        1 => 0.71821
                    ,2 => 0.20275
                    ,3 => 0.05498
                    ,4 => 0.01718
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "178" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "179" => [
                        1 => 0.72165
                    ,2 => 0.19931
                    ,3 => 0.04811
                    ,4 => 0.02405
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "180" => [
                        1 => 0.63574
                    ,2 => 0.24742
                    ,3 => 0.07560
                    ,4 => 0.03436
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "181" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "182" => [
                        1 => 0.63574
                    ,2 => 0.24742
                    ,3 => 0.08247
                    ,4 => 0.02749
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "183" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "184" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "185" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "186" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "187" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "188" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "189" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "190" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "191" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "192" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "193" => [
                        1 => 0.01718
                    ,2 => 0.03093
                    ,3 => 0.09278
                    ,4 => 0.33333
                    ,5 => 0.52577
                    ,6 => 0
                    ,7 => 0
                ],
            "194" => [
                        1 => 0.52234
                    ,2 => 0.31271
                    ,3 => 0.13058
                    ,4 => 0.02749
                    ,5 => 0.00687
                    ,6 => 0
                    ,7 => 0
                ],
            "195" => [
                        1 => 0.711340206
                    ,2 => 0.202749141
                    ,3 => 0.04467354
                    ,4 => 0.034364261
                    ,5 => 0.006872852
                    ,6 => 0
                    ,7 => 0
                ],
            "196" => [
                        1 => 0.305841924
                    ,2 => 0.202749141
                    ,3 => 0.137457045
                    ,4 => 0.254295533
                    ,5 => 0.099656357
                    ,6 => 0
                    ,7 => 0
                ],
            "197" => [
                        1 => 0.360824742
                    ,2 => 0.213058419
                    ,3 => 0.158075601
                    ,4 => 0.164948454
                    ,5 => 0.103092784
                    ,6 => 0
                    ,7 => 0
                ],
            "198" => [
                        1 => 0.656357388
                    ,2 => 0.206185567
                    ,3 => 0.085910653
                    ,4 => 0.041237113
                    ,5 => 0.010309278
                    ,6 => 0
                    ,7 => 0
                ],
            "199" => [
                        1 => 0.790378007
                    ,2 => 0.151202749
                    ,3 => 0.034364261
                    ,4 => 0.013745704
                    ,5 => 0.010309278
                    ,6 => 0
                    ,7 => 0
                ],
            "200" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "201" => [
                        1 => 0.776632302
                    ,2 => 0.144329897
                    ,3 => 0.051546392
                    ,4 => 0.024054983
                    ,5 => 0.003436426
                    ,6 => 0
                    ,7 => 0
                ],
            "202" => [
                        1 => 0.573883162
                    ,2 => 0.216494845
                    ,3 => 0.103092784
                    ,4 => 0.058419244
                    ,5 => 0.048109966
                    ,6 => 0
                    ,7 => 0
                ],
            "203" => [
                        1 => 0.701030928
                    ,2 => 0.202749141
                    ,3 => 0.068728522
                    ,4 => 0.024054983
                    ,5 => 0.003436426
                    ,6 => 0
                    ,7 => 0
                ],
            "204" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "205" => [
                        1 => 0.024054983
                    ,2 => 0.542955326
                    ,3 => 0.274914089
                    ,4 => 0.048109966
                    ,5 => 0.109965636
                    ,6 => 0
                    ,7 => 0
                ],
            "206" => [
                        1 => 0.041237113
                    ,2 => 0.030927835
                    ,3 => 0.020618557
                    ,4 => 0.171821306
                    ,5 => 0.735395189
                    ,6 => 0
                    ,7 => 0
                ],
            "207" => [
                        1 => 0.037800687
                    ,2 => 0.082474227
                    ,3 => 0.024054983
                    ,4 => 0.76975945
                    ,5 => 0.085910653
                    ,6 => 0
                    ,7 => 0
                ],
            "208" => [
                        1 => 0.553264605
                    ,2 => 0.140893471
                    ,3 => 0.079037801
                    ,4 => 0.085910653
                    ,5 => 0.140893471
                    ,6 => 0
                    ,7 => 0
                ],
            "209" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "210" => [
                        1 => 0.178694158
                    ,2 => 0.037800687
                    ,3 => 0.656357388
                    ,4 => 0.082474227
                    ,5 => 0.04467354
                    ,6 => 0
                    ,7 => 0
                ],
            "211" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "212" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "213" => [
                        1 => 0.030927835
                    ,2 => 0.006872852
                    ,3 => 0.859106529
                    ,4 => 0.020618557
                    ,5 => 0.082474227
                    ,6 => 0
                    ,7 => 0
                ],
            "214" => [
                        1 => 0.051546392
                    ,2 => 0.04467354
                    ,3 => 0.058419244
                    ,4 => 0.845360825
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "215" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "216" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "217" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "218" => [
                        1 => 0.109965636
                    ,2 => 0.130584192
                    ,3 => 0.16838488
                    ,4 => 0.020618557
                    ,5 => 0.570446735
                    ,6 => 0
                    ,7 => 0
                ],
            "219" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "220" => [
                        1 => 0.714776632
                    ,2 => 0.16838488
                    ,3 => 0.051546392
                    ,4 => 0.058419244
                    ,5 => 0.006872852
                    ,6 => 0
                    ,7 => 0
                ],
            "221" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "222" => [
                        1 => 0.054982818
                    ,2 => 0.092783505
                    ,3 => 0.099656357
                    ,4 => 0.065292096
                    ,5 => 0.687285223
                    ,6 => 0
                    ,7 => 0
                ],
            "223" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "224" => [
                        1 => 0.065292096
                    ,2 => 0.120274914
                    ,3 => 0.209621993
                    ,4 => 0.357388316
                    ,5 => 0.24742268
                    ,6 => 0
                    ,7 => 0
                ],
            "225" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "226" => [
                        1 => 0.649484536
                    ,2 => 0.237113402
                    ,3 => 0.075601375
                    ,4 => 0.020618557
                    ,5 => 0.017182131
                    ,6 => 0
                    ,7 => 0
                ],
            "227" => [
                        1 => 0.027491409
                    ,2 => 0.037800687
                    ,3 => 0.10652921
                    ,4 => 0.35395189
                    ,5 => 0.474226804
                    ,6 => 0
                    ,7 => 0
                ],
            "228" => [
                        1 => 0.034364261
                    ,2 => 0.099656357
                    ,3 => 0.164948454
                    ,4 => 0.412371134
                    ,5 => 0.288659794
                    ,6 => 0
                    ,7 => 0
                ],
            "229" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "230" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "231" => [
                        1 => 0.013745704
                    ,2 => 0.030927835
                    ,3 => 0.079037801
                    ,4 => 0.340206186
                    ,5 => 0.536082474
                    ,6 => 0
                    ,7 => 0
                ],
            "232" => [
                        1 => 0.024054983
                    ,2 => 0.051546392
                    ,3 => 0.130584192
                    ,4 => 0.302405498
                    ,5 => 0.491408935
                    ,6 => 0
                    ,7 => 0
                ],
            "233" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "234" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "235" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "236" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "237" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "238" => [
                        1 => 0.316151203
                    ,2 => 0.340206186
                    ,3 => 0.178694158
                    ,4 => 0.116838488
                    ,5 => 0.048109966
                    ,6 => 0
                    ,7 => 0
                ],
            "239" => [
                        1 => 0.048109966
                    ,2 => 0.12371134
                    ,3 => 0.18556701
                    ,4 => 0.340206186
                    ,5 => 0.302405498
                    ,6 => 0
                    ,7 => 0
                ],
            "240" => [
                        1 => 0.092783505
                    ,2 => 0.147766323
                    ,3 => 0.192439863
                    ,4 => 0.29209622
                    ,5 => 0.274914089
                    ,6 => 0
                    ,7 => 0
                ],
            "241" => [
                        1 => 0.024054983
                    ,2 => 0.082474227
                    ,3 => 0.18556701
                    ,4 => 0.381443299
                    ,5 => 0.326460481
                    ,6 => 0
                    ,7 => 0
                ],
            "242" => [
                        1 => 0.281786942
                    ,2 => 0.357388316
                    ,3 => 0.23024055
                    ,4 => 0.082474227
                    ,5 => 0.048109966
                    ,6 => 0
                    ,7 => 0
                ],
            "243" => [
                        1 => 0.034364261
                    ,2 => 0.075601375
                    ,3 => 0.158075601
                    ,4 => 0.312714777
                    ,5 => 0.419243986
                    ,6 => 0
                    ,7 => 0
                ],
            "244" => [
                        1 => 0.065292096
                    ,2 => 0.154639175
                    ,3 => 0.250859107
                    ,4 => 0.323024055
                    ,5 => 0.206185567
                    ,6 => 0
                    ,7 => 0
                ],
            "245" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "246" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "247" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "248" => [
                        1 => 0.010309278
                    ,2 => 0.010309278
                    ,3 => 0.037800687
                    ,4 => 0.226804124
                    ,5 => 0.714776632
                    ,6 => 0
                    ,7 => 0
                ],
            "249" => [
                        1 => 0
                    ,2 => 0
                    ,3 => 0
                    ,4 => 0
                    ,5 => 0
                    ,6 => 0
                    ,7 => 0
                ],
            "250" => [
                        1 => 0.412371134
                    ,2 => 0.278350515
                    ,3 => 0.189003436
                    ,4 => 0.082474227
                    ,5 => 0.037800687
                    ,6 => 0
                    ,7 => 0
                ],
            "251" => [
                    1 => 0.161512027
                    ,2 => 0.573883162
                    ,3 => 0.010309278
                    ,4 => 0.006872852
                    ,5 => 0.24742268
                    ,6 => 0
                    ,7 => 0
                ],
        ];
    }


    public function getEAS($score)
    {

        //スコアを得点に変更
        $array = $this->array_dp;
        $array_lv = $this->array_dp_lv;
        $read = 0;
        $chg = 0;
        $und = 0;
        $sel = 0;
        $infoP = 0;
        $infoM = 0;
        $point = [];

        foreach ($score as $k => $v) {
            if (!isset($array[$k][$v]) || $v == 0) {
                continue;
            }
            $point[ $k ] = round($array[ $k ][ $v ], 6);


            if ($k >= 1 && $k <= 30) {
                $read += round($array[ $k ][ $v ], 6);
            }
            if ($k >= 118 && $k <= 189) {
                $read += round($array[ $k ][ $v ], 6);
            }

            if ($k >= 31 && $k <= 60) {
                $chg += round($array[ $k ][ $v ], 6);
            }
            if ($k >= 188 && $k <= 204) {
                $chg += round($array[ $k ][ $v ], 6);
            }


            if ($k >= 61 && $k <= 82) {
                $und += round($array[ $k ][ $v ], 6);
            }
            if ($k >= 205 && $k <= 223) {
                $und += round($array[ $k ][ $v ], 6);
            }

            if ($k >= 83 && $k <= 117) {
                $sel += round($array[ $k ][ $v ], 6);
            }
            if ($k >= 224 && $k <= 251) {
                $sel += round($array[ $k ][ $v ], 6);
            }

            if ($k == 131
                || $k == 133
                || $k == 134
                || $k == 157
                || $k == 158
                || $k == 172
                || $k == 175
                || $k == 178
                || $k == 181
                || $k == 183
            ) {
                $infoP += $v;
            }
            if ($k == 125
                || $k == 126
                || $k == 128
                || $k == 132
                || $k == 135
                || $k == 155
                || $k == 159
                || $k == 174
                || $k == 176
                || $k == 177
                || $k == 179
                || $k == 180
                || $k == 182
            ) {
                $infoM += $v;
            }

        }
        $sougo = round(array_sum($point), 2);
        $read  = round($read, 2);
        $chg  = round($chg, 2);
        $und  = round($und, 2);
        $sel  = round($sel, 2);
        $info = round(($infoP / 10 - $infoM / 13), 2);

        //偏差値

        $sougo_hen = round((($sougo - $array_lv[ 'sougo'      ][ 'ave' ]) * 10) / $array_lv[ 'sougo'      ][ 'hen' ] + 50, 2);
        $read_hen  = round((($read - $array_lv[ 'read'        ][ 'ave' ]) * 10) / $array_lv[ 'read'       ][ 'hen' ] + 50, 2);
        $chg_hen   = round((($chg - $array_lv[ 'change'       ][ 'ave' ]) * 10) / $array_lv[ 'change'     ][ 'hen' ] + 50, 2);
        $und_hen   = round((($und - $array_lv[ 'understand'   ][ 'ave' ]) * 10) / $array_lv[ 'understand' ][ 'hen' ] + 50, 2);
        $sel_hen   = round((($sel - $array_lv[ 'select'       ][ 'ave' ]) * 10) / $array_lv[ 'select'     ][ 'hen' ] + 50, 2);
        $info_hen  = round((($info - $array_lv[ 'vaias'       ][ 'ave' ]) * 10) / $array_lv[ 'vaias'      ][ 'hen' ] + 50, 2);

        if ($sougo_hen <= 20) {
            $sougo_hen = 20;
        }
        if ($sougo_hen >= 80) {
            $sougo_hen = 80;
        }

        if ($read_hen <= 20) {
            $read_hen = 20;
        }
        if ($read_hen >= 80) {
            $read_hen = 80;
        }

        if ($chg_hen <= 20) {
            $chg_hen = 20;
        }
        if ($chg_hen >= 80) {
            $chg_hen = 80;
        }

        if ($und_hen <= 20) {
            $und_hen = 20;
        }
        if ($und_hen >= 80) {
            $und_hen = 80;
        }

        if ($sel_hen <= 20) {
            $sel_hen = 20;
        }
        if ($sel_hen >= 80) {
            $sel_hen = 80;
        }

        if ($info_hen <= 20) {
            $info_hen = 20;
        }
        if ($info_hen >= 80) {
            $info_hen = 80;
        }

        $lists = [];
        $lists = [
                    "sougo" => $sougo_hen
                    ,"yomitori" => $read_hen
                    ,"rikai" => $und_hen
                    ,"sentaku" => $sel_hen
                    ,"kirikae" => $chg_hen
                    ,"jyoho" => $info_hen
        ];
        return $lists;

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

}
