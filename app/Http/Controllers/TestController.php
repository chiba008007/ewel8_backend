<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\testparts;
use App\Models\testpdf;
use App\Models\User;
use App\Models\Exam;
use App\Models\exampfs;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DateTime;

class TestController extends UserController
{
    /*
        public function checkuser($user_id){
            $loginUser = auth()->user()->currentAccessToken();
            $admin_id = $loginUser->tokenable->id;
            // 管理者でログインしたとき
            if($loginUser->tokenable->type == "admin"){
                $result = User::select("id")->where("id",$user_id)->where("admin_id",$admin_id)->count();
                if($result < 1){
                    return false;
                }
                return true;
            }
            // パートナーでログイン
            if($loginUser->tokenable->type == "partner"){
                $subquery = User::select("admin_id")->where([
                    "id"=>$admin_id
                    ,"deleted_at"=>null
                ]);
                $result = DB::table('users')
                ->whereIn("admin_id",$subquery)
                ->where("id",$user_id)
                ->count();
                if($result < 1){
                    return false;
                }
                return true;
            }
            return false;
        }
    */

    public function getCsvList(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        $partner_id = $request->partner_id;
        try {
            $test = Test::Where([
                "user_id" => $user_id,
                "partner_id" => $partner_id
            ])->where("user_id", $user_id)->count();
            if ($test) {
                $result = testparts::Where(
                    [
                        "test_id" => $test_id
                        ,"status" => 1
                    ]
                )->get();
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            return response([], 201);
        }
        return response($result, 200);
    }
    public function getQRParam(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        try {
            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }
            $result = Test::Where("id", $test_id)->where("user_id", $user_id)->first();
        } catch (Exception $e) {
            return response([], 201);
        }
        return response($result, 200);
    }
    public function getQRLists(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        try {
            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }
            $passwd = config('const.consts.PASSWORD');
            $result = Exam::Select("test_id", "email", "password", "name")
            ->where([
                'test_id' => $test_id
                ,'customer_id' => $user_id
            ])
            ->whereNull('deleted_at')
            ->get();

            $list = [];
            $i = 0;
            foreach ($result as $value) {
                $pwd = openssl_decrypt($value[ 'password' ], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                // 初期パスワードは空欄で表示
                $list[$i]['no'] = $i + 1;
                $list[$i][ 'name'     ] = $value[ 'name' ];
                $list[$i][ 'exam_id'  ] = $value[ 'email' ];
                $list[$i][ 'password' ] = ($pwd == "password") ? "" : $pwd;
                $i++;
            }

        } catch (Exception $e) {
            return response([], 201);
        }

        return response($list, 200);
    }
    public function getTestList(Request $request)
    {
        $user_id = $request->user_id;
        $partner_id = $request->partner_id;
        try {
            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }
            $result = Test::LeftJoin('exams', function ($join) {
                $join
                ->on('exams.customer_id', '=', 'tests.customer_id')
                ->on('exams.partner_id', '=', 'tests.partner_id')
                ->on('exams.test_id', '=', 'tests.id')
                ->whereNull('exams.deleted_at')
                ;
            })
            ->Where(
                [
                    "tests.user_id" => $user_id,
                    "tests.partner_id" => $partner_id,
                    "tests.status" => 1
                ]
            )
            ->select(
                'tests.id',
                'tests.testname',
                'tests.testcount',
                'tests.startdaytime',
                'tests.enddaytime',
                DB::raw('
                COUNT(CASE WHEN exams.started_at IS NOT NULL  THEN 1 END) as syori,
                COUNT(CASE WHEN exams.ended_at IS NULL  THEN 1 END) as zan
                ')
            )
            ->groupBy('tests.id')
            ->orderBy('tests.startdaytime', 'desc')
            ->orderBy('tests.id', 'desc')
            ->get();
        } catch (Exception $e) {
            return response([], 201);
        }
        return response($result, 200);
    }

    public function getTestDetail(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        $partner_id = $request->partner_id;

        $rlt = [];
        $passwd = config('const.consts.PASSWORD');
        $rlt['exams'] = Exam::select([
            "exams.*",
            'tests.lisencedownloadflag'
            ])
        ->join('tests', 'exams.test_id', '=', 'tests.id')
        ->where("exams.test_id", $test_id)
        ->where("exams.customer_id", $user_id)
        ->where("exams.partner_id", $partner_id)
        ->whereNull("exams.deleted_at")
        ->withCount('pdfHistories')
        ->orderby("exams.id", "ASC")
        ->get();
        if (count($rlt[ 'exams' ])) {
            foreach ($rlt[ 'exams' ] as $key => $value) {
                $pwd = openssl_decrypt($value->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                $rlt[ 'exams' ][$key]->birth = ($pwd === "password" || $pwd === "Test") ? "" : $pwd;

                // デフォルト値
                $rlt['exams'][$key]->birth = "";
                $rlt['exams'][$key]->age = null;

                // 無効値はスキップ
                if ($pwd === null || $pwd === "password") {
                    continue;
                }
                $pwd = preg_replace('/^\xEF\xBB\xBF/', '', $pwd);
                $pwd = trim($pwd);
                // birth に設定
                $rlt['exams'][$key]->birth = $pwd;

                // "YYYY/MM/DD" の日付かをチェック
                $birth = DateTime::createFromFormat('Y/m/d', $pwd);
                if ($birth === false) {
                    continue; // パース失敗 → 年齢は null のまま
                }
                // updated_at を基準日にする
                $updatedAt = new DateTime($value->started_at);
                // 年齢を計算
                $age = $updatedAt->format('Y') - $birth->format('Y');
                // 誕生日がまだ来ていなければ -1
                if (
                    (int)$updatedAt->format('md') < (int)$birth->format('md')
                ) {
                    $age--;
                }
                $rlt['exams'][$key]->age = $age;
            }
        } else {
            return response([], 200);
        }
        $testparts = testparts::where("testparts.test_id", $test_id)
            ->get();
        $pfsArray = [];

        foreach ($testparts as $key => $value) {
            if ($value['code'] === 'PFS') {
                $pfsArray = $this->getPFSDetail($test_id, $value[ 'threeflag' ]);
                break;
            }
        }

        foreach ($rlt[ 'exams' ] as $key => $value) {
            // PFSデータの表示
            $rlt['exams'][$key]['pfs'] = (isset($pfsArray[$value->id])) ? $pfsArray[$value->id] : [];
        }
        return response($rlt, 200);
    }
    public function getTestEditData(Request $request)
    {
        $id = $request->id;
        $user_id = $request->user_id;
        if ($this->checkuser($user_id)) {
            $rlt = [];
            $rlt['test'] =
            Test::select(["tests.*"])
            ->where([
                'id' => $id,
                'user_id' => $user_id,
                'status' => 1
            ])
            ->first();
            $testparts = testparts::where([
                'test_id' => $id,
                'status' => 1
            ])
            ->get();
            $list = [];
            foreach ($testparts as $value) {
                $list[$value->code] = $value;
            }
            $rlt['testparts'] = $list;


            $rlt['testpdf'] = testpdf::where([
                'test_id' => $id,
                'status' => 1
            ])
            ->get();

            // 削除できるテスト残り数
            $examcount = Exam::where([
                "test_id" => $id,
                'customer_id' => $user_id,
            ])
            ->whereNotNull("started_at")
            ->whereNull("deleted_at")
            ->count();

            $rlt['done'] = $examcount;
            return response($rlt, 200);
        }
        return response([], 201);
    }
    private function getPFSDetail($test_id, $threeflag = 0)
    {
        $code = "PFS";
        $sql = "
            SELECT *
            FROM (
                SELECT
                    e.exam_id,
                    e.testparts_id,
                    DATE_FORMAT(e.starttime, '%Y/%m/%d') AS starttime,
                    DATE_FORMAT(e.endtime, '%Y/%m/%d') AS endtime,
                    e.id,
                    e.level,
                    e.dev1,
                    e.dev2,
                    e.dev3,
                    e.dev6,
                    ROW_NUMBER() OVER (
                        PARTITION BY e.exam_id, e.testparts_id
                        ORDER BY e.id DESC
                    ) AS rn
                FROM exampfses e
                WHERE e.testparts_id = (
                    SELECT id FROM testparts WHERE test_id = ? AND code = ?
                )
            ) t
            WHERE t.rn = 1
        ";
        $pfsdetails = DB::select($sql, [$test_id, $code]);
        $pfsArray = [];
        foreach ($pfsdetails as $value) {
            $pfsArray[$value->exam_id]['starttime'] = $value->starttime;
            $pfsArray[$value->exam_id]['endtime'] = $value->endtime;
            $pfsArray[$value->exam_id]['level'] = $value->level;
            $pfsArray[$value->exam_id]['dev1'] = $value->dev1;
            $pfsArray[$value->exam_id]['dev2'] = $value->dev2;
            $pfsArray[$value->exam_id]['dev3'] = $value->dev3;
            $pfsArray[$value->exam_id]['dev6'] = $value->dev6;
            if ($threeflag) {
                list($lv, $score) = $this->getStress2($value->dev1, $value->dev2, $value->dev6);
            } else {
                list($lv, $score) = $this->getStress($value->dev1, $value->dev2);
            }
            $pfsArray[$value->exam_id]['lv'] = $lv;
            $pfsArray[$value->exam_id]['score'] = $score;
        }
        return $pfsArray;
    }


    //ストレスデータ取得ストレスフラグ無し
    public static function getStress($dev1, $dev2)
    {
        $ave = ($dev1 + $dev2) / 2;
        $roundedAve = round($ave, 1);
        if ($ave < 30) {
            $st_level = 1;
            $st_score = $roundedAve;
        } elseif ($ave < 35) {
            if ($dev1 < 40 && $dev2 < 40) {
                $st_level = 1;
                $st_score = $roundedAve;
            } else {
                $st_level = 2;
                $st_score = 35;
            }
        } elseif ($ave < 40) {
            if ($dev1 < 40 && $dev2 < 40) {
                $st_level = 1;
                $st_score = 34.9;
            } elseif ($dev1 < 30 || $dev2 < 30) {
                $st_level = 2;
                $st_score = $roundedAve;
            } else {
                $st_level = 3;
                $st_score = 45;
            }
        } elseif ($ave < 45) {
            if ($dev1 < 30 || $dev2 < 30) {
                $st_level = 2;
                $st_score = $roundedAve;
            } elseif ($dev1 < 50 && $dev2 < 50) {
                $st_level = 3;
                $st_score = 45;
            } else {
                $st_level = 4;
                $st_score = 55;
            }
        } elseif ($ave < 50) {
            if ($dev1 < 30 || $dev2 < 30) {
                $st_level = 2;
                $st_score = 44.9;
            } elseif ($dev1 < 50 && $dev2 < 50) {
                $st_level = 3;
                $st_score = $roundedAve;
            } else {
                $st_level = 4;
                $st_score = 55;
            }
        } elseif ($ave < 55) {
            if ($dev1 < 30 || $dev2 < 30) {
                $st_level = 2;
                $st_score = 44.9;
            } else {
                $st_level = 4;
                $st_score = 55;
            }
        } elseif ($ave < 60) {
            if ($dev1 < 50 || $dev2 < 50) {
                $st_level = 4;
                $st_score = $roundedAve;
            } elseif ($dev1 < 60 && $dev2 < 60) {
                $st_level = 4;
                $st_score = $roundedAve;
            } else {
                $st_level = 5;
                $st_score = 65;
            }
        } elseif ($ave < 65) {
            if ($dev1 < 50 || $dev2 < 50) {
                $st_level = 4;
                $st_score = $roundedAve;
            } else {
                $st_level = 5;
                $st_score = 65;
            }
        } else {
            $st_level = 5;
            $st_score = $roundedAve;
        }
        return array($st_level, $st_score);
    }


    //ストレスデータ取得ストレスフラグあり
    public static function getStress2($dev1, $dev2, $dev3)
    {

        $dev1 = sprintf("%s", ($dev1 >= 70) ? 60 : $dev1);
        $dev2 = sprintf("%s", ($dev2 >= 70) ? 60 : $dev2);
        $dev3 = sprintf("%s", ($dev3 >= 70) ? 60 : $dev3);

        $dev1 = sprintf("%s", ($dev1 <= 35.21) ? 20 : $dev1);
        $dev2 = sprintf("%s", ($dev2 <= 35.21) ? 20 : $dev2);
        $dev3 = sprintf("%s", ($dev3 <= 35.21) ? 20 : $dev3);

        //ポジティブ思考力スコア反転
        $dev3 = 100 - $dev3;

        $ave = ($dev1 + $dev2 + $dev3) / 3;
        $st_score = round($ave, 1);
        if ($ave >= 64.79) {
            $st_level = 5;
        } elseif ($ave >= 54.49) {
            $st_level = 4;
        } elseif ($ave >= 45.3) {
            $st_level = 3;
        } elseif ($ave >= 35) {
            $st_level = 2;
        } else {
            $st_level = 1;
        }

        return array($st_level, $st_score);
    }

    // 重複を取り除く関数
    public function removeDuplicates($array)
    {
        // 配列の重複を削除
        return array_unique($array);
    }

    // 重複をチェックして必要な部分を再構築
    public function checkAndRebuild($array, $testcount)
    {
        $str = config('const.consts.alpha');
        // 重複している値を検出
        $duplicates = array_diff_assoc($array, array_unique($array));
        if (!empty($duplicates)) {
            // echo "重複があります。重複部分を作り直します。\n";
            // 重複部分を取り除いて新しい配列を作成
            $newArray = $this->removeDuplicates($array);
            while (true) {
                foreach ($duplicates as $key => $value) {
                    $newArray[$key] = substr(str_shuffle(str_repeat($str, 10)), 0, 3);
                }
                if ($testcount == count($newArray)) {
                    break;
                }
            }

            // 重複部分が取り除かれた配列を返す
            return $newArray;
        } else {
            //echo "重複はありません。\n";
            return $array;
        }
    }

    public function setTest(Request $request)
    {
        $query = substr(bin2hex(random_bytes(8)), 0, 8);


        $lisence = config('const.consts.LISENCE');



        DB::beginTransaction();
        try {
            $user_id = $request->user_id;
            //所定のユーザーIDが利用可能かチェック
            $loginUser = auth()->user()->currentAccessToken();
            $admin_id = $loginUser->tokenable->id;

            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }
            $params = [];
            $params["params"] = $query;
            $params["partner_id"] = $request->partner_id;
            $params["customer_id"] = $request->customer_id;
            $params["user_id"] = $request->user_id;
            $params["testname"] = $request->testname;
            $params["testcount"] = $request->testcount;
            $params["nameuseflag"] = $request->nameuseflag;
            $params["genderuseflag"] = $request->genderuseflag;
            $params["mailremaincount"] = $request->mailremaincount;
            $params["startdaytime"] = $request->startdaytime;
            $params["enddaytime"] = $request->enddaytime;
            $params["resultflag"] = $request->resultflag;
            $params["envcheckflag"] = $request->envcheckflag;
            $params["enqflag"] = $request->enqflag;
            $params["lisencedownloadflag"] = $request->lisencedownloadflag;
            $params["examlistdownloadflag"] = $request->examlistdownloadflag;
            $params["totaldownloadflag"] = $request->totaldownloadflag;
            $params["recomendflag"] = $request->recomendflag;
            $params["loginflag"] = $request->loginflag;
            $params["logintext"] = $request->logintext;
            $params["movietype"] = $request->movietype;
            $params["moviedisplayurl"] = $request->moviedisplayurl;
            $params["pdfuseflag"] = $request->pdfuseflag;
            $params["pdfstartday"] = $request->pdfstartday;
            $params["pdfendday"] = $request->pdfendday;
            $params["pdfcountflag"] = $request->pdfcountflag;
            $params["pdflimitcount"] = $request->pdflimitcount;
            $params["status"] = $request->status;
            $params["created_at"] = date("Y-m-d H:i:s");
            Test::insert($params);
            $id = DB::getPdo()->lastInsertId();
            $pdf = $request->pdf;

            foreach ($pdf as $key => $value) {
                if ($key > 0 && $value['value']) {
                    $params = [];
                    $params['test_id'] = $id;
                    $params['pdf_id'] = $value['key'];
                    $params[ 'status' ] = 1;
                    $params[ 'created_at'] = date("Y-m-d H:i:s");
                    if (!testpdf::insert($params)) {
                        throw new Exception();
                    }
                }
            }

            $parts = $request->parts;

            $lisence = config('const.consts.LISENCE');

            $codePfs = $lisence[1]['list'][5]['code'];
            $codeBAJ3 = preg_replace("/\-/", "", $lisence[1]['list'][3]['code']);

            foreach ($parts as $key => $value) {
                $params = [];
                if (
                    (isset($value[$codePfs]) &&  $value[$codePfs]) //PFSの登録
                ) {
                    $codePfs = $lisence[1]['list'][5]['code'];
                    $params[ 'test_id' ] = $id;
                    $params[ 'code' ] = $codePfs;
                    $params[ 'status' ] = $value[ $codePfs ][ 'status' ] ? 1 : 0;
                    $params[ 'threeflag' ] = $value[ $codePfs ][ 'threeflag' ] ? 1 : 0;
                    $params[ 'weightFlag' ] = $value[ $codePfs ][ 'weightFlag' ] ? 1 : 0;
                    $params[ 'created_at' ] = date("Y-m-d H:i:s");
                    for ($i = 1;$i <= 14;$i++) {
                        $w = "weight".$i;
                        $params[$w] = (isset($value[$codePfs][ 'weight' ][$i])) ? $value[$codePfs][ 'weight' ][$i] : 0;
                    }

                    if (!testparts::insert($params)) {
                        throw new Exception();
                    }
                }

                if (
                    (isset($value[$codeBAJ3]) &&  $value[$codeBAJ3]) //BAJ3の登録
                ) {
                    $params[ 'test_id' ] = $id;
                    $params[ 'code' ] = $codeBAJ3;
                    $params[ 'status' ] = $value[ $codeBAJ3 ][ 'status' ] ? 1 : 0;
                    $params[ 'threeflag' ] = $value[ $codeBAJ3 ][ 'threeflag' ] ? 1 : 0;
                    $params[ 'weightFlag' ] = $value[ $codeBAJ3 ][ 'weightFlag' ] ? 1 : 0;
                    $params[ 'created_at' ] = date("Y-m-d H:i:s");
                    for ($i = 1;$i <= 14;$i++) {
                        $w = "weight".$i;
                        $params[$w] = (isset($value[$codeBAJ3][ 'weight' ][$i])) ? $value[$codeBAJ3][ 'weight' ][$i] : 0;
                    }

                    if (!testparts::insert($params)) {
                        throw new Exception();
                    }
                }

            }


            $this->testExamsInsert($query, $request, $id, $request->testcount);

            DB::commit();
            return response('success', 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response('error', 201);
        }

    }

    public function editTest(Request $request)
    {
        DB::beginTransaction();
        try {
            $user_id = $request->user_id;
            $customer_id = $request->customer_id;
            $edit_id = $request->edit_id;
            //所定のユーザーIDが利用可能かチェック
            $loginUser = auth()->user()->currentAccessToken();
            $admin_id = $loginUser->tokenable->id;

            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }
            $test = Test::find($edit_id);
            $basetestcount = $test->testcount;
            $params = $test->params;
            // 受検者数の確認
            // 登録元の人数より少ない人数を指定するとき
            $deleteCount = 0;
            $addCount = 0;
            if ($request->testcount === 0) {
                return response('zero success', 200);
            }

            if ($basetestcount > $request->testcount) {
                $usedTestCount = Exam::where([
                    "test_id" => $edit_id,
                    "customer_id" => $customer_id,
                    "deleted_at" => null,
                    "name" => null
                    ])->count();
                if ($basetestcount - $usedTestCount > $request->testcount) {
                    // 想定以上の数の削除を行っているためエラー
                    echo "count Over";
                    throw new Exception();
                }
                $deleteCount = $basetestcount - $request->testcount;
            }
            if ($basetestcount < $request->testcount) {
                $addCount = $request->testcount - $basetestcount;
            }
            // テストを減らしたさいのテスト削除
            $ids = Exam::select("exams.id")
                ->where([
                    "test_id" => $edit_id,
                    "customer_id" => $customer_id,
                    "deleted_at" => null,
                    "name" => null
                ])
                ->orderBy('id', 'desc')
                ->take($deleteCount)
                ->pluck('id');
            Exam::whereIn('id', $ids)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            $this->testExamsInsert($params, $request, $edit_id, $addCount);

            $test->testname = $request->testname;
            $test->testcount = $request->testcount;
            $test->nameuseflag = $request->nameuseflag;
            $test->genderuseflag = $request->genderuseflag;
            $test->mailremaincount = $request->mailremaincount;
            $test->startdaytime = $request->startdaytime;
            $test->enddaytime = $request->enddaytime;
            $test->resultflag = $request->resultflag;
            $test->envcheckflag = $request->envcheckflag;
            $test->enqflag = $request->enqflag;
            $test->lisencedownloadflag = $request->lisencedownloadflag;
            $test->examlistdownloadflag = $request->examlistdownloadflag;
            $test->totaldownloadflag = $request->totaldownloadflag;
            $test->recomendflag = $request->recomendflag;
            $test->loginflag = $request->loginflag;
            $test->logintext = $request->logintext;
            $test->movietype = $request->movietype;
            $test->moviedisplayurl = $request->moviedisplayurl;
            $test->pdfuseflag = $request->pdfuseflag;
            $test->pdfstartday = $request->pdfstartday;
            $test->pdfendday = $request->pdfendday;
            $test->pdfcountflag = $request->pdfcountflag;
            $test->pdflimitcount = $request->pdflimitcount;
            $test->save();

            testpdf::where('test_id', $edit_id)
            ->update(['status' => 0]);

            $pdf = $request->pdf;

            foreach ($pdf as $key => $value) {
                if (isset($value[ 'value' ]) && $value[ 'value' ] &&  $key > 0) {
                    $params = [];
                    $params['test_id'] = $edit_id;
                    $params['pdf_id'] = $value['key'];
                    $params[ 'status' ] = 1;
                    $params[ 'created_at'] = date("Y-m-d H:i:s");
                    if (!testpdf::insert($params)) {
                        throw new Exception();
                    }
                }
            }

            $parts = $request->parts;
            $lisence = config('const.consts.LISENCE');
            $codePfs = $lisence[1]['list'][5]['code'];
            $codeBAJ3 = preg_replace("/\-/", "", $lisence[1]['list'][3]['code']);

            foreach ($parts as $key => $value) {
                $params = [];
                if (
                    (isset($value[$codePfs]) &&
                    $value[$codePfs] &&
                    $value[$codePfs][ 'status' ]) //PFSの登録
                ) {
                    $pfs = testparts::where([
                        'test_id' => $edit_id,
                        'code' => $codePfs,
                        'status' => 1
                    ])->first();

                    $pfs->threeflag = $value[$codePfs]['threeflag'] ? 1 : 0;
                    $pfs->weightFlag = $value[$codePfs]['weightFlag'] ? 1 : 0;
                    $pfs->save();

                }

                if (
                    (
                        isset($value[$codeBAJ3]) &&
                        $value[$codeBAJ3] &&
                        $value[$codeBAJ3][ 'status' ]
                    ) //BAJ3の登録
                ) {
                    $baj3 = testparts::where([
                        'test_id' => $edit_id,
                        'code' => $codeBAJ3,
                        'status' => 1
                    ])->first();
                    $baj3->threeflag = $value[$codeBAJ3]['threeflag'] ? 1 : 0;
                    $baj3->weightFlag = $value[$codeBAJ3]['weightFlag'] ? 1 : 0;
                    $baj3->save();
                }

            }

            DB::commit();
            return response('success', 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response($e, 201);
        }
    }

    public function testExamsInsert($query, $request, $id, $testcount)
    {
        $passwd = config('const.consts.PASSWORD');
        // テスト一覧修正
        // 顧客用IDの作成
        $str = config('const.consts.alpha');
        $aExamid = [];
        for ($i = 0; $i < $testcount; $i++) {
            $aExamid[] = substr(str_shuffle(str_repeat($str, 10)), 0, 3);
        }
        $aExamid = $this->checkAndRebuild($aExamid, $testcount);
        $lists = [];
        $aChank = array_chunk($aExamid, 100, true);
        foreach ($aChank as $cValue) {
            foreach ($cValue as $cKey => $val) {
                $lists[$cKey][ 'test_id'     ] = $id;
                $lists[$cKey][ 'partner_id'  ] = $request->partner_id;
                $lists[$cKey][ 'customer_id' ] = $request->customer_id;
                $lists[$cKey][ 'param'    ] = $query;
                $lists[$cKey][ 'email'    ] = $val;
                $lists[$cKey][ 'password' ] = openssl_encrypt('password', 'aes-256-cbc', $passwd[ 'key' ], 0, $passwd[ 'iv' ]);
                $lists[$cKey][ 'created_at' ] = date('Y-m-d H:i:s');
            }
        }
        if (!Exam::Insert($lists)) {
            throw new Exception();
        }
    }

    public function getTestTableTh(Request $request)
    {
        $user_id = $request->user_id;
        if (!$this->checkuser($user_id)) {
            throw new Exception();
        }
        $test_id = $request->test_id;
        $data = Test::select("testparts.code")
            ->join("testparts", "testparts.test_id", "=", "tests.id")
            ->where("tests.user_id", $user_id)
            ->where("testparts.test_id", $test_id)
            ->where("tests.status", 1)
            ->where("testparts.status", 1)
            ->get();

        return response($data, 200);
    }

    public function getPFSTestDetail(Request $request)
    {

        $exam_id = $request->exam_id;

        $sql = "
            SELECT
                *
            FROM
                exampfses
            WHERE
                id =
            (
                SELECT
                    MAX(id) as id
                FROM
                    exampfses
                WHERE
                    exam_id=?
                GROUP BY exam_id,testparts_id
            )
            ";
        $pfsdetails = DB::select($sql, [$exam_id]);
        $ans_data = config('const.PFS3.PFS3');
        $result = $ans_data[$pfsdetails[0]->soyo];
        return response($result, 200);
    }

    public function getSearchExam()
    {
        // $loginUser = auth()->user()->currentAccessToken();
        $data = Exam::select([
            'exams.*',
            'tests.testname as testname',
            "users_customer.name as customer_name",
            "users_partner.name as partner_name",
            "endtime"
        ])
        ->leftjoin("tests", "tests.id", "=", "exams.test_id")
        ->leftjoin("users as users_customer", "users_customer.id", "=", "exams.customer_id")
        ->leftjoin("users as users_partner", "users_partner.id", "=", "exams.partner_id")
        ->leftjoin("exampfses", function ($join) {
            $join
            ->select("MAX(endtime)")
            ->on('exams.id', '=', 'exampfses.exam_id')
            ->where('exampfses.status', "=", 1);
        })
        ->where("exams.name", "!=", "''")
        ->ORDERBY("exampfses.endtime", "DESC");
        return response($data->get(), 200);
    }
    public function deleteTest(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        if (!$this->checkuser($user_id)) {
            throw new Exception();
        }
        Log::info('検査削除実施:'.$request);
        DB::beginTransaction();
        try {

            $rlt = Test::where([
                'id' => $test_id,
                'user_id' => $user_id
                ])
            ->update(['status' => 0]);
            Exam::where([
                'test_id' => $test_id,
                'customer_id' => $user_id
                ])
            ->update(['deleted_at' => date('Y-m-d')]);
            Log::info('検査削除実施成功');
            DB::commit();
            return response("success", 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::info('検査削除実施失敗'.$e);

            return response($e, 400);
        }
    }
    public function getTest(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        if (!$this->checkuser($user_id)) {
            throw new Exception();
        }
        DB::beginTransaction();
        try {
            $data = Test::selectRaw(
                "id,
                    user_id,
                    testname,
                    pdfstartday,
                    pdfendday,
                    pdfuseflag,
                    DATE_FORMAT(startdaytime, '%Y/%m/%d') as formatted_startdaytime,DATE_FORMAT(enddaytime, '%Y/%m/%d') as formatted_enddaytime"
            )
                ->where([
            'id' => $test_id,
            'user_id' => $user_id
            ])
                ->first();
            DB::commit();
            return response($data, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response($e, 400);
        }
    }
}
