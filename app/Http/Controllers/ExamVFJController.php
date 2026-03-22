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

    // public function setStarttime()
    // {
    //     $loginUser = auth()->user()->currentAccessToken();

    //     $flight = Exam::find($loginUser->tokenable->id);
    //     if ($flight->started_at == null) {
    //         $flight->started_at =  date('Y-m-d H:i:s');
    //         $flight->save();
    //     }
    //     return response(true, 200);
    // }

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
    // public function getExamTestParts(Request $request)
    // {
    //     $result = [];
    //     $params = $request->params;
    //     $testparts_id = $request->testparts_id;
    //     try {
    //         $result = Test::select(
    //             "testparts.*"
    //         )
    //         ->leftJoin("testparts", "testparts.test_id", "=", "tests.id")
    //         ->where("params", $params)
    //         ->where("testparts.id", $testparts_id)
    //         ->first();
    //     } catch (Exception $e) {
    //         return response([], 400);
    //     }
    //     return response($result, 200);
    // }

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


    // public function resultPFS($testparts_id)
    // {

    //     /** @var \App\Models\User $user */
    //     $user = auth()->user();
    //     $token = $user->currentAccessToken();
    //     $exam_id = $token->tokenable->id;

    //     // 最後の1件を取得
    //     $last = exampfs::select("*")->latest("id")->where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();
    //     // 重みデータ取得
    //     $testparts = testparts::where("id", $testparts_id)->first();
    //     $weights = [];
    //     for ($i = 1;$i <= 14;$i++) {
    //         if ($i == 13) {
    //             $weights[ "sd" ] = $testparts[ 'weight'.$i ];
    //         }
    //         if ($i == 14) {
    //             $weights[ "ave" ] = $testparts[ 'weight'.$i ];
    //         }
    //         if ($i >= 1 && $i <= 12) {
    //             $weights["w".$i] = $testparts[ 'weight'.$i ];
    //         }

    //     }

    //     $raw_data = [
    //         1 => ['-:q4','+:q10','-:q14','+:q24','-:q25', '+:q31', 'モニタリング' ],
    //         2 => ['-:q2','+:q12','-:q13','+:q19','-:q28', '+:q36', '適切な自己評価' ],
    //         3 => ['-:q1','+:q7' ,'-:q16','+:q22','-:q26', '+:q34', '肯定的自己像' ],
    //         4 => ['-:q7','+:q8' ,'-:q17','+:q23','-:q27', '+:q28', '克己抑制' ],
    //         5 => ['-:q5','+:q11','-:q15','+:q16','-:q31', '+:q35', '達成動機' ],
    //         6 => ['-:q3','+:q4' ,'-:q19','+:q20','-:q29', '+:q32', '楽観性' ],
    //         7 => ['+:q5','-:q10','-:q20','+:q21','+:q26', '-:q30', '共感性' ],
    //         8 => ['-:q8','+:q9' ,'+:q14','-:q18','+:q33', '-:q34', 'センシブル' ],
    //         9 => ['+:q2','-:q6' ,'+:q17','-:q22','+:q29', '-:q32', 'サービス精神' ],
    //         10 => [ '+:q3',  '-:q12', '+:q18', '-:q23', '+:q25', '-:q33', 'リーダーシップ' ],
    //         11 => [ '+:q6',  '-:q11', '+:q13', '-:q21', '+:q30', '-:q36', 'アサーション' ],
    //         12 => [ '+:q1',  '-:q9' , '+:q15', '-:q24', '+:q27', '-:q35', 'チームワーク' ]
    //     ];

    //     $dev_data = [
    //         1 => [-2.22094564737075,3.83810209584864,'モニタリング'],
    //         2 => [-0.607158638974812,3.40571923193921,'適切な自己評価'],
    //         3 => [-3.52261010458094,3.5371486665457,'肯定的自己像'],
    //         4 => [-1.63816467815584,3.45374535910044,'克己抑制'],
    //         5 => [0.0233465900721756,3.4974037775748,'達成動機'],
    //         6 => [1.22433348063043,3.52077641367019,'楽観性'],
    //         7 => [2.23619089703933,3.94365874100903,'共感性'],
    //         8 => [1.54116953896008,3.39747930063903,'センシブル'],
    //         9 => [1.18846663720725,3.48025199197222,'サービス精神'],
    //         10 => [ -0.486669612608632,4.32246024919477,'リーダーシップ'],
    //         11 => [ -0.524230372661659,3.30271067209346,'アサーション'],
    //         12 => [ 2.78627191044336,3.49133504881389,'チームワーク'],
    //     ];
    //     list($row, $lv, $standard_score, $dev_number) = $this->BA12($last, $weights, $raw_data, $dev_data);

    //     // PFS結果の計算
    //     $pfsdata = $this->calcPFS($row);
    //     // var_dump($row,$lv,$standard_score,$dev_number);
    //     $exam = exampfs::find($last[ 'id' ]);
    //     $exam->endtime = date("Y-m-d H:i:s");
    //     $exam->dev1 = $row['dev1'];
    //     $exam->dev2 = $row['dev2'];
    //     $exam->dev3 = $row['dev3'];
    //     $exam->dev4 = $row['dev4'];
    //     $exam->dev5 = $row['dev5'];
    //     $exam->dev6 = $row['dev6'];
    //     $exam->dev7 = $row['dev7'];
    //     $exam->dev8 = $row['dev8'];
    //     $exam->dev9 = $row['dev9'];
    //     $exam->dev10 = $row['dev10'];
    //     $exam->dev11 = $row['dev11'];
    //     $exam->dev12 = $row['dev12'];
    //     $exam->soyo = $dev_number;
    //     $exam->level = $lv;
    //     $exam->score = $standard_score;
    //     $exam->sougo = $pfsdata['sougo'];
    //     $exam->personal = $pfsdata['personal'];
    //     $exam->state = $pfsdata['state'];
    //     $exam->job = $pfsdata['job'];
    //     $exam->image = $pfsdata['image'];
    //     $exam->positive = $pfsdata['positive'];
    //     $exam->self = $pfsdata['self'];

    //     $exam->save();


    //     return response("success", 200);
    // }

    // public function calcPFS($row)
    // {
    //     $return = array();
    //     $return['personal'] = sprintf("%.1f", round(100 - $row['dev7'], 1));
    //     $return['state'   ] = sprintf("%.1f", round(100 - $row['dev8'], 1));
    //     $return['job'     ] = sprintf("%.1f", round($row['dev2'], 1));
    //     $return['image'   ] = sprintf("%.1f", round(100 - $row['dev4'], 1));
    //     $return['positive'] = sprintf("%.1f", round($row['dev6'], 1));
    //     $return['self'    ] = sprintf("%.1f", round($row['dev3'], 1));
    //     $return['sougo'   ] = sprintf("%.1f", $this->getSougo($row));

    //     return $return;
    // }
    // public function getSougo($set)
    // {
    //     $point = 0.5;

    //     if ($set['dev6'] - $set[ 'dev7' ] >= 5
    //         and  $set['dev6'] - $set[ 'dev7' ] < 10
    //     ) {
    //         $point += 3;
    //     } elseif ($set['dev6'] - $set[ 'dev7' ] >= 10) {
    //         $point += 4;
    //     }

    //     if ($set['dev3'] - $set[ 'dev4' ] >= 5
    //         and  $set['dev3'] - $set[ 'dev4' ] < 10
    //     ) {
    //         $point += 2;
    //     } elseif ($set['dev3'] - $set[ 'dev4' ] >= 10) {
    //         $point += 3;
    //     }

    //     if ($set['dev8'] < 45) {
    //         $point += 1;
    //     }

    //     if ($set['dev2'] >= 52) {
    //         $point += 0.5;
    //     }

    //     if ($set[ 'dev11' ] - $set[ 'dev7' ] >= 5) {
    //         $point += 1;
    //     }

    //     return $point;

    // }

    // public function BA12($line, $row2, $raw_data, $dev_data, $flg = "")
    // {
    //     // 素点算出
    //     // 準備 [q1～q36の値を-3する]
    //     $q = "";
    //     if ($flg) {
    //         $k = 13;
    //         for ($num = 1; $num <= 36; $num++) {
    //             $q = "q".$k;
    //             $row["q$num"] = $line[$q] - 3;
    //             $k++;
    //         }
    //     } else {
    //         for ($num = 1; $num <= 36; $num++) {
    //             $q = "q".$num;
    //             $row["q$num"] = $line[$q] - 3;
    //         }
    //     }
    //     // 素点計算
    //     $dev = array();
    //     $dev_count = 1;
    //     // 素点データ読み込み
    //     foreach ($raw_data as $rawkey => $rawval) {
    //         $pm_data = array();
    //         // キーNoの比較

    //         if ($rawkey == $dev_count) {
    //             $dev[$rawkey] = 0;
    //             for ($num = 0; $num <= 5; $num++) {
    //                 // 各要素の値を分解（+,-と比較問題に分ける）
    //                 $pm_data = explode(':', $raw_data[$rawkey][$num]);
    //                 if ($pm_data[0] == '+') {
    //                     $dev[$rawkey] = $dev[$rawkey] + $row["$pm_data[1]"];
    //                 } elseif ($pm_data[0] == '-') {
    //                     $dev[$rawkey] = $dev[$rawkey] - $row["$pm_data[1]"];
    //                 }
    //             }
    //         }
    //         $dev_count++;
    //     }

    //     //読み込み
    //     // ステップ②
    //     // 偏差値算出
    //     $dev_count = 1;

    //     // 比較用dev
    //     $result_dev = array();
    //     // 偏差値データの読み込み
    //     foreach ($dev_data as $dkey => $dval) {
    //         // キーNoの比較
    //         if ($dkey == $dev_count) {
    //             $row["dev$dkey"] = 0;
    //             for ($num = 0; $num <= 1; $num++) {
    //                 // それぞれの値を計算
    //                 //自己感情モニタリング力
    //                 $devskey = "dev".$dkey;
    //                 if ($devskey == 'dev1') {
    //                     $row["dev$dkey"] = 100 - ((($dev[$dkey] - $dev_data[$dkey][0]) / $dev_data[$dkey][1]) * 10 + 50) + 3.5;
    //                 } elseif ($devskey == 'dev2') {
    //                     $row["dev$dkey"] = 100 - ((($dev[$dkey] - $dev_data[$dkey][0]) / $dev_data[$dkey][1]) * 10 + 50) + 0.7;
    //                 } else {
    //                     $row["dev$dkey"] = (($dev[$dkey] - $dev_data[$dkey][0]) / $dev_data[$dkey][1]) * 10 + 50;
    //                 }

    //                 if ($row["dev$dkey"] >= 80) {
    //                     $row["dev$dkey"] = 80;
    //                 }
    //                 if ($row["dev$dkey"] <= 20) {
    //                     $row["dev$dkey"] = 20;
    //                 }

    //                 // 比較用データを作成
    //                 $result_dev[$dkey] = $row["dev$dkey"];

    //             }
    //         }
    //         $dev_count++;
    //     }
    //     // 総合得点素点算出(おもみ付け)
    //     $all_score =
    //     (round($row['dev1'], 1) * $row2['w1']) +
    //     (round($row['dev2'], 1) * $row2['w2']) +
    //     (round($row['dev3'], 1) * $row2['w3']) +
    //     (round($row['dev4'], 1) * $row2['w4']) +
    //     (round($row['dev5'], 1) * $row2['w5']) +
    //     (round($row['dev6'], 1) * $row2['w6']) +
    //     (round($row['dev7'], 1) * $row2['w7']) +
    //     (round($row['dev8'], 1) * $row2['w8']) +
    //     (round($row['dev9'], 1) * $row2['w9']) +
    //     (round($row['dev10'], 1) * $row2['w10']) +
    //     (round($row['dev11'], 1) * $row2['w11']) +
    //     (round($row['dev12'], 1) * $row2['w12']);


    //     // 総合得点の偏差値算出

    //     if ($row2['sd'] > 0) {
    //         $standard_score = (($all_score - $row2['ave']) / $row2['sd']) * 10 + 50;
    //     } else {
    //         $standard_score = 0;
    //     }
    //     if ($standard_score >= 80) {
    //         $standard_score = 80;
    //     }
    //     if ($standard_score <= 20) {
    //         $standard_score = 20;
    //     }

    //     $lv = '';

    //     if ($standard_score <= 80 && $standard_score >= 65) {
    //         $lv = 5;
    //     } elseif ($standard_score < 65 && $standard_score >= 55) {
    //         $lv = 4;
    //     } elseif ($standard_score < 55 && $standard_score >= 45) {
    //         $lv = 3;
    //     } elseif ($standard_score < 45 && $standard_score >= 35) {
    //         $lv = 2;
    //     } elseif ($standard_score < 35 && $standard_score >= 20) {
    //         $lv = 1;
    //     } else {
    //         ;
    //     }

    //     $max_dev = max($result_dev);
    //     $dev_number = 0;
    //     for ($dcount = 1; $dcount <= 12; $dcount++) {
    //         if ($row["dev$dcount"] == $max_dev && $dev_number == 0) {
    //             $dev_number = $dcount;
    //         }
    //     }

    //     return array($row,$lv,$standard_score,$dev_number);

    // }

    public function test()
    {
        $loginUser = auth()->user()->currentAccessToken();
        return response($loginUser, 200);
    }
}
