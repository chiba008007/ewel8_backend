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
use App\Services\ExamAuthService;
use App\Services\ExamProfileService;
use App\Services\TestExamMenuService;

class ExamController extends Controller
{
    /**
     * 検査ログイン
     */
    public function index(Request $request, ExamAuthService $service)
    {
        try {
            $response = $service->authenticate($request);

            // 成功ログ：個人情報・tokenは出さない
            Log::info('検査ログイン成功', [
                'user_id' => $response['user']['id'] ?? null,
                'test_id' => $request->input('test_id'),
                'ip' => $request->ip(),
            ]);

            return response($response, 200);

        } catch (\Throwable $e) {
            // ログインIDなどの個人情報はそのまま出さない
            Log::warning('検査ログイン失敗', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
                'test_id' => $request->input('test_id'),
            ]);

            // 画面には詳細を返さない
            return response('error', 400);
        }
    }

    public function setStarttime()
    {
        $loginUser = auth()->user();
        $flight = Exam::find($loginUser->id);
        if ($flight->started_at == null) {
            $flight->started_at =  date('Y-m-d H:i:s');
            $flight->save();
        }
        return response(true, 200);
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
        // 受信値を検証する
        $validated = $request->validate([
            'params' => ['required', 'string'],
            'testparts_id' => ['required', 'integer'],
        ]);

        $result = Test::select([
                'users.company_name',
                'tests.*',
            ])
            // URLのtestparts_idが対象検査に属するか確認する
            ->join('testparts', 'testparts.test_id', '=', 'tests.id')
            ->leftJoin('users', 'users.id', '=', 'tests.user_id')
            ->where('tests.params', $validated['params'])
            ->where('testparts.id', $validated['testparts_id'])
            ->where('testparts.status', 1)
            ->where('tests.status', 1)
            ->where('tests.startdaytime', '<=', now())
            ->where('tests.enddaytime', '>=', now())
            ->first();

        if (!$result) {
            // URLを書き換えた場合は404にする
            return response()->json([], 404);
        }

        return response()->json($result);
    }

    public function getExamList()
    {

        return response([], 200);
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
        /** @var \App\Models\Exam $user */
        $user = auth()->user();

        $params = [
            'name'   => $request->name,
            'kana'   => $request->kana,
            'gender' => $request->gender,
        ];

        try {
            $service->updateProfile(
                $user->id,
                $user->email,
                $request->k,
                $params
            );

            // 個人情報の値はログに出さない
            Log::info('検査プロフィール更新成功', [
                'exam_id' => $user->id,
                'test_key' => $request->k,
                'ip' => $request->ip(),
            ]);

            return response(true, 200);

        } catch (\Throwable $e) {
            // name/kana/gender は出さない
            Log::error('検査プロフィール更新失敗', [
                'exam_id' => $user->id ?? null,
                'test_key' => $request->k,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
            ]);

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
        /*
            $loginUser = auth()->user()->currentAccessToken();
            $examid = $loginUser->tokenable->id;
            $params = $request->params;
            try {
                $result = Test::select(
                    "tests.*",
                    "testparts.code",
                    "testparts.id as testparts_id",
                    "examfins.status as examstatus"
                )
                // ->leftJoin("testparts","testparts.test_id","=","tests.id")
                ->leftJoin("testparts", function ($join) {
                    $join->on("testparts.test_id", "=", "tests.id")
                    ->where("testparts.status", "=", 1);
                })
                ->leftJoin("examfins", function ($join) use ($examid) {
                    $join->on("examfins.testparts_id", "=", "testparts.id")
                    ->where("examfins.exam_id", "=", $examid);
                })
                ->where("params", $params)
                ->get();


            } catch (Exception $e) {
                return response([], 201);
            }
            return response($result, 200);
        */
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
    public function getExamTestParts(Request $request)
    {
        $result = [];
        $params = $request->params;
        $testparts_id = $request->testparts_id;
        try {
            $result = Test::select(
                "testparts.*"
            )
            ->leftJoin("testparts", "testparts.test_id", "=", "tests.id")
            ->where("params", $params)
            ->where("testparts.id", $testparts_id)
            ->first();
        } catch (Exception $e) {
            return response([], 201);
        }
        return response($result, 200);
    }

    /**
     * 回答済みの範囲を確認する
     */
    private function hasPfsAnswers(exampfs $exam, int $start, int $end): bool
    {
        for ($i = $start; $i <= $end; $i++) {
            // 0を回答値として扱えるよう、nullだけを未回答とする
            if ($exam->getAttribute('q'.$i) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * 現在表示可能な最大ページを取得する
     */
    private function getAllowedPfsPage(exampfs $exam): int
    {
        if (!$this->hasPfsAnswers($exam, 1, 10)) {
            return 1;
        }

        if (!$this->hasPfsAnswers($exam, 11, 20)) {
            return 2;
        }

        if (!$this->hasPfsAnswers($exam, 21, 30)) {
            return 3;
        }

        return 4;
    }

    public function getPFS(Request $request)
    {
        $validated = $request->validate([
            'params' => ['required', 'string'],
            'testparts_id' => ['required', 'integer'],
            'page' => ['required', 'integer', 'between:1,4'],
        ]);

        /** @var \App\Models\Exam $user */
        $user = auth()->user();

        // URLの検査IDとparamsの組み合わせを確認する
        $validTestpart = Test::join(
            'testparts',
            'testparts.test_id',
            '=',
            'tests.id'
        )
            ->where('tests.params', $validated['params'])
            ->where('tests.status', 1)
            ->where('testparts.id', $validated['testparts_id'])
            ->where('testparts.status', 1)
            ->exists();

        if (!$validTestpart) {
            return response()->json([
                'message' => '対象の検査が存在しません。',
            ], 404);
        }

        $last = exampfs::where('testparts_id', $validated['testparts_id'])
            ->where('exam_id', $user->id)
            ->latest('id')
            ->first();

        // 検査開始前にtake画面へ直接アクセスした場合
        if (!$last) {
            return response()->json([
                'message' => '検査が開始されていません。',
            ], 404);
        }

        $allowedPage = $this->getAllowedPfsPage($last);

        // 未回答ページを飛ばした場合
        if ($validated['page'] > $allowedPage) {
            return response()->json([
                'message' => '前のページの回答が完了していません。',
                'allowed_page' => $allowedPage,
            ], 409);
        }

        if ($last->endtime && $last->soyo !== null) {
            $answerData = config('const.PFS3.PFS3');

            // 結果番号が存在する場合だけ設定する
            $last->result = $answerData[$last->soyo] ?? null;
        }

        $last->allowed_page = $allowedPage;

        return response()->json($last);
    }

    public function setPFS(Request $request)
    {
        /** @var \App\Models\Exam $user */
        $user = auth()->user();

        // 認証済み受検者ID
        $exam_id = $user->id;
        $testparts_id = $request->testparts_id;

        Log::info('PFS開始API到達', [
            'exam_id' => $exam_id,
            'testparts_id' => $testparts_id,
            'ip' => $request->ip(),
        ]);

        try {
            DB::transaction(function () use ($exam_id, $testparts_id) {
                // 既存の有効ステータスを無効化
                exampfs::where('exam_id', $exam_id)
                    ->where('status', 1)
                    ->update(['status' => 0]);

                // 新しい受検データを作成
                exampfs::create([
                    'testparts_id' => $testparts_id,
                    'exam_id' => $exam_id,
                    'status' => 1,
                ]);
            });

            Log::info('PFS開始成功', [
                'exam_id' => $exam_id,
                'testparts_id' => $testparts_id,
            ]);

            return response('success', 200);

        } catch (\Throwable $e) {
            Log::error('PFS開始失敗', [
                'exam_id' => $exam_id ?? null,
                'testparts_id' => $testparts_id ?? null,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
            ]);

            return response('error', 500);
        }
    }

    public function editPFS(Request $request)
    {
        /** @var \App\Models\Exam $user */
        $user = auth()->user();

        // 認証済み受検者ID
        $exam_id = $user->id;
        $testparts_id = $request->testparts_id;

        Log::info('PFS検査回答登録開始', [
            'exam_id' => $exam_id,
            'testparts_id' => $testparts_id,
            'page' => $request->page,
            'ip' => $request->ip(),
        ]);

        try {
            DB::transaction(function () use ($request, $exam_id, $testparts_id) {
                // 最新の受検データを取得
                $exam = exampfs::where('testparts_id', $testparts_id)
                    ->where('exam_id', $exam_id)
                    ->latest('id')
                    ->firstOrFail();

                // 2ページ目で開始時刻を更新
                if ((int) $request->page === 2) {
                    $exam->starttime = now();
                }

                // 回答を登録
                foreach ($request->selectPoint as $key => $value) {
                    $exam['q' . $key] = $value;
                }

                $exam->save();

                // 最終ページなら採点・完了処理
                if ((int) $request->page === 5) {
                    // PFS結果を計算・保存
                    $this->resultPFS($testparts_id);

                    Log::info('PFS採点結果保存成功', [
                        'exam_id' => $exam_id,
                        'testparts_id' => $testparts_id,
                        'exampfs_id' => $exam->id,
                    ]);

                    // 検査完了登録
                    examfins::complete($exam_id, $testparts_id);

                    Log::info('PFS検査完了登録成功', [
                        'exam_id' => $exam_id,
                        'testparts_id' => $testparts_id,
                        'exampfs_id' => $exam->id,
                    ]);

                    // 受検者全体の終了時刻更新
                    exam::setEndTime();

                    Log::info('受検者終了時刻更新成功', [
                        'exam_id' => $exam_id,
                    ]);

                    // 残数メール処理
                    exam::sendRemainMail($request);

                    Log::info('残数メール処理成功', [
                        'exam_id' => $exam_id,
                        'testparts_id' => $testparts_id,
                    ]);
                }
            });

            return response('success', 200);

        } catch (\Throwable $e) {
            Log::error('PFS検査回答登録失敗', [
                'exam_id' => $exam_id,
                'testparts_id' => $testparts_id,
                'page' => $request->page,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
            ]);

            return response('error', 500);
        }
    }

    public function downloadExam()
    {
        $loginUser = auth()->user()->currentAccessToken();
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
    public function resultPFS($testparts_id)
    {

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $token = $user->currentAccessToken();
        $exam_id = $token->tokenable->id;

        // 最後の1件を取得
        $last = exampfs::select("*")->latest("id")->where("testparts_id", $testparts_id)->where("exam_id", $exam_id)->first();
        // 重みデータ取得
        $testparts = testparts::where("id", $testparts_id)->first();
        $weights = [];
        for ($i = 1;$i <= 14;$i++) {
            if ($i == 13) {
                $weights[ "sd" ] = $testparts[ 'weight'.$i ];
            }
            if ($i == 14) {
                $weights[ "ave" ] = $testparts[ 'weight'.$i ];
            }
            if ($i >= 1 && $i <= 12) {
                $weights["w".$i] = $testparts[ 'weight'.$i ];
            }

        }

        $raw_data = [
            1 => ['-:q4','+:q10','-:q14','+:q24','-:q25', '+:q31', 'モニタリング' ],
            2 => ['-:q2','+:q12','-:q13','+:q19','-:q28', '+:q36', '適切な自己評価' ],
            3 => ['-:q1','+:q7' ,'-:q16','+:q22','-:q26', '+:q34', '肯定的自己像' ],
            4 => ['-:q7','+:q8' ,'-:q17','+:q23','-:q27', '+:q28', '克己抑制' ],
            5 => ['-:q5','+:q11','-:q15','+:q16','-:q31', '+:q35', '達成動機' ],
            6 => ['-:q3','+:q4' ,'-:q19','+:q20','-:q29', '+:q32', '楽観性' ],
            7 => ['+:q5','-:q10','-:q20','+:q21','+:q26', '-:q30', '共感性' ],
            8 => ['-:q8','+:q9' ,'+:q14','-:q18','+:q33', '-:q34', 'センシブル' ],
            9 => ['+:q2','-:q6' ,'+:q17','-:q22','+:q29', '-:q32', 'サービス精神' ],
            10 => [ '+:q3',  '-:q12', '+:q18', '-:q23', '+:q25', '-:q33', 'リーダーシップ' ],
            11 => [ '+:q6',  '-:q11', '+:q13', '-:q21', '+:q30', '-:q36', 'アサーション' ],
            12 => [ '+:q1',  '-:q9' , '+:q15', '-:q24', '+:q27', '-:q35', 'チームワーク' ]
        ];

        $dev_data = [
            1 => [-2.22094564737075,3.83810209584864,'モニタリング'],
            2 => [-0.607158638974812,3.40571923193921,'適切な自己評価'],
            3 => [-3.52261010458094,3.5371486665457,'肯定的自己像'],
            4 => [-1.63816467815584,3.45374535910044,'克己抑制'],
            5 => [0.0233465900721756,3.4974037775748,'達成動機'],
            6 => [1.22433348063043,3.52077641367019,'楽観性'],
            7 => [2.23619089703933,3.94365874100903,'共感性'],
            8 => [1.54116953896008,3.39747930063903,'センシブル'],
            9 => [1.18846663720725,3.48025199197222,'サービス精神'],
            10 => [ -0.486669612608632,4.32246024919477,'リーダーシップ'],
            11 => [ -0.524230372661659,3.30271067209346,'アサーション'],
            12 => [ 2.78627191044336,3.49133504881389,'チームワーク'],
        ];
        list($row, $lv, $standard_score, $dev_number) = $this->BA12($last, $weights, $raw_data, $dev_data);

        // PFS結果の計算
        $pfsdata = $this->calcPFS($row);
        // var_dump($row,$lv,$standard_score,$dev_number);
        $exam = exampfs::find($last[ 'id' ]);
        $exam->endtime = date("Y-m-d H:i:s");
        $exam->dev1 = $row['dev1'];
        $exam->dev2 = $row['dev2'];
        $exam->dev3 = $row['dev3'];
        $exam->dev4 = $row['dev4'];
        $exam->dev5 = $row['dev5'];
        $exam->dev6 = $row['dev6'];
        $exam->dev7 = $row['dev7'];
        $exam->dev8 = $row['dev8'];
        $exam->dev9 = $row['dev9'];
        $exam->dev10 = $row['dev10'];
        $exam->dev11 = $row['dev11'];
        $exam->dev12 = $row['dev12'];
        $exam->soyo = $dev_number;
        $exam->level = $lv;
        $exam->score = $standard_score;
        $exam->sougo = $pfsdata['sougo'];
        $exam->personal = $pfsdata['personal'];
        $exam->state = $pfsdata['state'];
        $exam->job = $pfsdata['job'];
        $exam->image = $pfsdata['image'];
        $exam->positive = $pfsdata['positive'];
        $exam->self = $pfsdata['self'];

        $exam->save();


        return response("success", 200);
    }

    public function calcPFS($row)
    {
        $return = array();
        $return['personal'] = sprintf("%.1f", round(100 - $row['dev7'], 1));
        $return['state'   ] = sprintf("%.1f", round(100 - $row['dev8'], 1));
        $return['job'     ] = sprintf("%.1f", round($row['dev2'], 1));
        $return['image'   ] = sprintf("%.1f", round(100 - $row['dev4'], 1));
        $return['positive'] = sprintf("%.1f", round($row['dev6'], 1));
        $return['self'    ] = sprintf("%.1f", round($row['dev3'], 1));
        $return['sougo'   ] = sprintf("%.1f", $this->getSougo($row));

        return $return;
    }
    public function getSougo($set)
    {
        $point = 0.5;

        if ($set['dev6'] - $set[ 'dev7' ] >= 5
            and  $set['dev6'] - $set[ 'dev7' ] < 10
        ) {
            $point += 3;
        } elseif ($set['dev6'] - $set[ 'dev7' ] >= 10) {
            $point += 4;
        }

        if ($set['dev3'] - $set[ 'dev4' ] >= 5
            and  $set['dev3'] - $set[ 'dev4' ] < 10
        ) {
            $point += 2;
        } elseif ($set['dev3'] - $set[ 'dev4' ] >= 10) {
            $point += 3;
        }

        if ($set['dev8'] < 45) {
            $point += 1;
        }

        if ($set['dev2'] >= 52) {
            $point += 0.5;
        }

        if ($set[ 'dev11' ] - $set[ 'dev7' ] >= 5) {
            $point += 1;
        }

        return $point;

    }

    public function BA12($line, $row2, $raw_data, $dev_data, $flg = "")
    {
        // 素点算出
        // 準備 [q1～q36の値を-3する]
        $q = "";
        if ($flg) {
            $k = 13;
            for ($num = 1; $num <= 36; $num++) {
                $q = "q".$k;
                $row["q$num"] = $line[$q] - 3;
                $k++;
            }
        } else {
            for ($num = 1; $num <= 36; $num++) {
                $q = "q".$num;
                $row["q$num"] = $line[$q] - 3;
            }
        }
        // 素点計算
        $dev = array();
        $dev_count = 1;
        // 素点データ読み込み
        foreach ($raw_data as $rawkey => $rawval) {
            $pm_data = array();
            // キーNoの比較

            if ($rawkey == $dev_count) {
                $dev[$rawkey] = 0;
                for ($num = 0; $num <= 5; $num++) {
                    // 各要素の値を分解（+,-と比較問題に分ける）
                    $pm_data = explode(':', $raw_data[$rawkey][$num]);
                    if ($pm_data[0] == '+') {
                        $dev[$rawkey] = $dev[$rawkey] + $row["$pm_data[1]"];
                    } elseif ($pm_data[0] == '-') {
                        $dev[$rawkey] = $dev[$rawkey] - $row["$pm_data[1]"];
                    }
                }
            }
            $dev_count++;
        }

        //読み込み
        // ステップ②
        // 偏差値算出
        $dev_count = 1;

        // 比較用dev
        $result_dev = array();
        // 偏差値データの読み込み
        foreach ($dev_data as $dkey => $dval) {
            // キーNoの比較
            if ($dkey == $dev_count) {
                $row["dev$dkey"] = 0;
                for ($num = 0; $num <= 1; $num++) {
                    // それぞれの値を計算
                    //自己感情モニタリング力
                    $devskey = "dev".$dkey;
                    if ($devskey == 'dev1') {
                        $row["dev$dkey"] = 100 - ((($dev[$dkey] - $dev_data[$dkey][0]) / $dev_data[$dkey][1]) * 10 + 50) + 3.5;
                    } elseif ($devskey == 'dev2') {
                        $row["dev$dkey"] = 100 - ((($dev[$dkey] - $dev_data[$dkey][0]) / $dev_data[$dkey][1]) * 10 + 50) + 0.7;
                    } else {
                        $row["dev$dkey"] = (($dev[$dkey] - $dev_data[$dkey][0]) / $dev_data[$dkey][1]) * 10 + 50;
                    }

                    if ($row["dev$dkey"] >= 80) {
                        $row["dev$dkey"] = 80;
                    }
                    if ($row["dev$dkey"] <= 20) {
                        $row["dev$dkey"] = 20;
                    }

                    // 比較用データを作成
                    $result_dev[$dkey] = $row["dev$dkey"];

                }
            }
            $dev_count++;
        }
        // 総合得点素点算出(おもみ付け)
        $all_score =
        (round($row['dev1'], 1) * $row2['w1']) +
        (round($row['dev2'], 1) * $row2['w2']) +
        (round($row['dev3'], 1) * $row2['w3']) +
        (round($row['dev4'], 1) * $row2['w4']) +
        (round($row['dev5'], 1) * $row2['w5']) +
        (round($row['dev6'], 1) * $row2['w6']) +
        (round($row['dev7'], 1) * $row2['w7']) +
        (round($row['dev8'], 1) * $row2['w8']) +
        (round($row['dev9'], 1) * $row2['w9']) +
        (round($row['dev10'], 1) * $row2['w10']) +
        (round($row['dev11'], 1) * $row2['w11']) +
        (round($row['dev12'], 1) * $row2['w12']);


        // 総合得点の偏差値算出

        if ($row2['sd'] > 0) {
            $standard_score = (($all_score - $row2['ave']) / $row2['sd']) * 10 + 50;
        } else {
            $standard_score = 0;
        }
        if ($standard_score >= 80) {
            $standard_score = 80;
        }
        if ($standard_score <= 20) {
            $standard_score = 20;
        }

        $lv = '';

        if ($standard_score <= 80 && $standard_score >= 65) {
            $lv = 5;
        } elseif ($standard_score < 65 && $standard_score >= 55) {
            $lv = 4;
        } elseif ($standard_score < 55 && $standard_score >= 45) {
            $lv = 3;
        } elseif ($standard_score < 45 && $standard_score >= 35) {
            $lv = 2;
        } elseif ($standard_score < 35 && $standard_score >= 20) {
            $lv = 1;
        } else {
            ;
        }

        $max_dev = max($result_dev);
        $dev_number = 0;
        for ($dcount = 1; $dcount <= 12; $dcount++) {
            if ($row["dev$dcount"] == $max_dev && $dev_number == 0) {
                $dev_number = $dcount;
            }
        }

        return array($row,$lv,$standard_score,$dev_number);

    }

    public function test()
    {
        $loginUser = auth()->user()->currentAccessToken();
        return response($loginUser, 200);
    }
}
