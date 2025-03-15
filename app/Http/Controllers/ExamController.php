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

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    function index(Request $request)
    {

        $passwd = config('const.consts.PASSWORD');
        $userdata = Exam::where('email', $request->email)->where("test_id",$request->test_id)->first();
        $user = Exam::find($userdata[ 'id' ]);
        // パスワードがデフォルト状態(password)の時、パスワードの再設定を行う
        if(openssl_decrypt($user['password'], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']) == "password"){
            $pwd = openssl_encrypt($request->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
            $user->password = $pwd;
            $user->save();

            $user = Exam::find($userdata[ 'id' ]);

        }
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

    function setStarttime(){
        $loginUser = auth()->user()->currentAccessToken();

        $flight = Exam::find($loginUser->tokenable->id);
        if($flight->started_at == null){
            $flight->started_at =  date('Y-m-d H:i:s');
            $flight->save();
        }
        return response(true, 200);
    }

    function checkStatus(Request $request){
        $loginUser = auth()->user()->currentAccessToken();
        $exam_id = $loginUser->tokenable->id;
        $testparts_id = $request->testparts_id;

        $last = examfins::Where("testparts_id",$testparts_id)->where("exam_id",$exam_id)->first();
        if($last && $last->status == 1){
            return response(true, 200);
        }else{
            return response(false, 200);
        }


    }
    function getExam(Request $request){
        $now = date("Y-m-d H:i:s");
        try{
            $rlt = Test::
            select(["users.company_name","tests.*"])
            ->where("params",$request->params)
            ->where("status",1)
            ->where("startdaytime","<=",$now)
            ->where("enddaytime",">=",$now)
            ->leftjoin("users","users.id","=","tests.user_id")
            ->first();
            if($rlt){
                return response($rlt, 200);
            }else{
                return response([],400);
            }
        }catch(Exception $e){
            return response([],400);
        }
    }
    function getExamList(){

        return response([],200);
    }
    function getExamData(){
        try{
            $loginUser = auth()->user()->currentAccessToken();
            $passwd = config('const.consts.PASSWORD');
            $name = explode("　",$loginUser->tokenable->name);
            $kana = explode("　",$loginUser->tokenable->kana);
            $gender = $loginUser->tokenable->gender;
            $pwd = openssl_decrypt($loginUser->tokenable->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
            $loginUser->password = $pwd;

            $loginUser->name1 = (isset($name[0]))?$name[0]:"";
            $loginUser->name2 = (isset($name[1]))?$name[1]:"";
            $loginUser->kana1 = (isset($kana[0]))?$kana[0]:"";
            $loginUser->kana2 = (isset($kana[1]))?$kana[1]:"";
            $loginUser->gender = $gender;
            if(!$loginUser){
                return response([],200);
            }
            return response($loginUser, 200);
        }catch(Exception $e){
            return response(false,200);
        }

    }

    function editExamData(Request $request){
        $loginUser = auth()->user()->currentAccessToken();
        $id = $loginUser->tokenable->id;
        $k = $request->k;
        $params = [];
        $params['name'] = $request->name;
        $params['kana'] = $request->kana;
        $params['gender'] = $request->gender;
        DB::beginTransaction();
        try{
            //
            //Exam::find($id)->update($params);
            $exam_ids = Exam::select("id")
            ->where("param",$k)
            ->where("email",$loginUser->tokenable->email)
            ->get();
            foreach($exam_ids as $value){
                if($value[ 'id' ]){
                    Exam::find($value[ 'id' ])->update($params);
                }else{
                    throw new Exception();
                }
            }
            DB::commit();
            return response(true, 200);
        }catch(Exception $e){
            DB::rollBack();
            return response(false, 400);
        }

    }

    public function getTestExamMenu(Request $request){
        $loginUser = auth()->user()->currentAccessToken();
        $examid = $loginUser->tokenable->id;
        $params = $request->params;
        try{
            $result = Test::select(
                "tests.*","testparts.code","testparts.id as testparts_id","examfins.status as examstatus"
            )
            ->leftJoin("testparts","testparts.test_id","=","tests.id")
            //->leftJoin("examfins","examfins.testparts_id","=","testparts.id")
            ->leftJoin("examfins",function($join) use($examid)  {
                $join->on("examfins.testparts_id","=","testparts.id")
                ->where("examfins.exam_id","=",$examid);
            })
            ->where("params",$params)
            ->get();


        }catch(Exception $e){
            return response([], 400);
        }
        return response($result, 200);
    }
    function getExamTestParts(Request $request){
        $result = [];
        $params = $request->params;
        $testparts_id = $request->testparts_id;
        try{
            $result = Test::select(
                "testparts.*"
            )
            ->leftJoin("testparts","testparts.test_id","=","tests.id")
            ->where("params",$params)
            ->where("testparts.id",$testparts_id)
            ->first();
        }catch(Exception $e){
            return response([], 400);
        }
        return response($result, 200);
    }

    function getPFS(Request $request){

        $loginUser = auth()->user()->currentAccessToken();
        $exam_id = $loginUser->tokenable->id;
        $testparts_id = $request->testparts_id;
        // 最後の1件を取得
        $last = exampfs::select("*")->latest("id")->where("testparts_id",$testparts_id)->where("exam_id",$exam_id)->first();
        // 結果データがあるときは結果をまとめて取得
        if($last->endtime){
            $ans_data = config('const.consts.PFS3');
            $last->result = $ans_data[$last->soyo];
        }
        return response($last, 200);
    }
    function setPFS(Request $request){
        $loginUser = auth()->user()->currentAccessToken();
        $exam_id = $loginUser->tokenable->id;

        // 既存のテストステータスを0にする
        exampfs::where("exam_id","=",$exam_id)
        ->where("status","=",1)
        ->update(['status'=>0]);

        $params = [];
        $params[ 'testparts_id' ] = $request->testparts_id;
        $params[ 'exam_id' ] = $exam_id;
        $params[ 'status' ] = 1;
        $params[ 'created_at' ] = date("Y-m-d H:i:s");
        $params[ 'updated_at' ] = date("Y-m-d H:i:s");
        try{
            if(exampfs::insert($params)){
                return response("success", 200);
            }else{
                throw new Exception();
            }
        }catch(Exception $e){
            return response("error", 400);
        }
    }
    function editPFS(Request $request){
        $loginUser = auth()->user()->currentAccessToken();
        $exam_id = $loginUser->tokenable->id;
        $testparts_id = $request->testparts_id;
        // 最後の1件を取得
        $last = exampfs::select("id")->latest("id")->where("testparts_id",$testparts_id)->where("exam_id",$exam_id)->first();
        $exam = exampfs::find($last[ 'id' ]);
        if($request->page == 2){
            $exam->starttime = date("Y-m-d H:i:s");
        }

        $selectPoint = $request->selectPoint;
        foreach($selectPoint as $key => $value){
            $q = "q".$key;
            $exam[$q] = $value;
        }
        $exam->save();

        // 最後のページ
        if($request->page == 5){

            // 計算
            $this->resultPFS($testparts_id);
            $params = [];
            $params[ 'exam_id' ] = $exam_id;
            $params[ 'testparts_id' ] = $testparts_id;
            $params[ 'status' ] = 1;
            $params[ 'created_at' ] = date("Y-m-d H:i:s");
            $params[ 'updated_at' ] = date("Y-m-d H:i:s");
            examfins::insert($params);

            // 最終登録データ確認
            exam::setEndTime();
        }
        return response("success", 200);
    }
    function downloadExam(){
        $loginUser = auth()->user()->currentAccessToken();
        $id = $loginUser->tokenable->id;
        $code = $loginUser->tokenable->email;
        $password = $loginUser->tokenable->password;
        $passwd = config('const.consts.PASSWORD');
        $decript = openssl_decrypt($password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
        $decript = preg_replace("/\//","-",$decript);
        //header("Location:/pdf/".$id."/code/".$code."/birth/".$decript);
        $params = [];
        $params[ 'id' ] = $id;
        $params[ 'code' ] = $code;
        $params[ 'decript' ] = $decript;
        return response($params, 200);
        exit();

    }
    function resultPFS($testparts_id){
        $loginUser = auth()->user()->currentAccessToken();
        $exam_id = $loginUser->tokenable->id;
        // 最後の1件を取得
        $last = exampfs::select("*")->latest("id")->where("testparts_id",$testparts_id)->where("exam_id",$exam_id)->first();
        // 重みデータ取得
        $testparts = testparts::where("id",$testparts_id)->first();
        $weights = [];
        for($i=1;$i<=14;$i++){
            if($i == 13) $weights[ "sd" ] = $testparts[ 'weight'.$i ];
            if($i == 14) $weights[ "ave" ] = $testparts[ 'weight'.$i ];
            if($i >= 1 && $i <= 12) $weights["w".$i] = $testparts[ 'weight'.$i ];

        }

        $raw_data = [
            1=>['-:q4','+:q10','-:q14','+:q24','-:q25', '+:q31', 'モニタリング' ],
            2=>['-:q2','+:q12','-:q13','+:q19','-:q28', '+:q36', '適切な自己評価' ],
            3=>['-:q1','+:q7' ,'-:q16','+:q22','-:q26', '+:q34', '肯定的自己像' ],
            4=>['-:q7','+:q8' ,'-:q17','+:q23','-:q27', '+:q28', '克己抑制' ],
            5=>['-:q5','+:q11','-:q15','+:q16','-:q31', '+:q35', '達成動機' ],
            6=>['-:q3','+:q4' ,'-:q19','+:q20','-:q29', '+:q32', '楽観性' ],
            7=>['+:q5','-:q10','-:q20','+:q21','+:q26', '-:q30', '共感性' ],
            8=>['-:q8','+:q9' ,'+:q14','-:q18','+:q33', '-:q34', 'センシブル' ],
            9=>['+:q2','-:q6' ,'+:q17','-:q22','+:q29', '-:q32', 'サービス精神' ],
            10 => [ '+:q3',  '-:q12', '+:q18', '-:q23', '+:q25', '-:q33', 'リーダーシップ' ],
            11 => [ '+:q6',  '-:q11', '+:q13', '-:q21', '+:q30', '-:q36', 'アサーション' ],
            12 => [ '+:q1',  '-:q9' , '+:q15', '-:q24', '+:q27', '-:q35', 'チームワーク' ]
        ];

        $dev_data = [
            1 =>[-2.22094564737075,3.83810209584864,'モニタリング'],
            2 =>[-0.607158638974812,3.40571923193921,'適切な自己評価'],
            3 =>[-3.52261010458094,3.5371486665457,'肯定的自己像'],
            4 =>[-1.63816467815584,3.45374535910044,'克己抑制'],
            5 =>[0.0233465900721756,3.4974037775748,'達成動機'],
            6 =>[1.22433348063043,3.52077641367019,'楽観性'],
            7 =>[2.23619089703933,3.94365874100903,'共感性'],
            8 =>[1.54116953896008,3.39747930063903,'センシブル'],
            9 =>[1.18846663720725,3.48025199197222,'サービス精神'],
            10 =>[ -0.486669612608632,4.32246024919477,'リーダーシップ'],
            11 =>[ -0.524230372661659,3.30271067209346,'アサーション'],
            12 =>[ 2.78627191044336,3.49133504881389,'チームワーク'],
        ];
        list($row,$lv,$standard_score,$dev_number) = $this->BA12($last ,$weights,$raw_data,$dev_data);

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

        $exam->save();


        return response("success",200);
    }

    function BA12($line,$row2,$raw_data,$dev_data,$flg=""){
        // 素点算出
        // 準備 [q1～q36の値を-3する]
        $q = "";
        if($flg){
            $k = 13;
            for ( $num = 1; $num <= 36; $num++ ) {
                $q = "q".$k;
                $row["q$num"] = $line[$q] - 3;
                $k++;
            }
        }else{
            for ( $num = 1; $num <= 36; $num++ ) {
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

            if ( $rawkey == $dev_count ) {
                $dev[$rawkey] = 0;
                for ( $num = 0; $num <= 5; $num++ ) {
                    // 各要素の値を分解（+,-と比較問題に分ける）
                    $pm_data = explode(':',$raw_data[$rawkey][$num] );
                    if( $pm_data[0] == '+' ) {
                        $dev[$rawkey] = $dev[$rawkey] + $row["$pm_data[1]"];
                    }elseif ( $pm_data[0] == '-' ) {
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
            if ( $dkey == $dev_count ) {
                $row["dev$dkey"] = 0;
                for ( $num = 0; $num <= 1; $num++ ) {
                    // それぞれの値を計算
                    //自己感情モニタリング力
                    $devskey = "dev".$dkey;
                    if($devskey == 'dev1' ){
                        $row["dev$dkey"] = 100- ( ( ( $dev[$dkey] - $dev_data[$dkey][0] ) / $dev_data[$dkey][1] ) * 10 + 50  )+3.5;
                    }elseif($devskey == 'dev2' ){
                        $row["dev$dkey"] = 100- ( ( ( $dev[$dkey] - $dev_data[$dkey][0] ) / $dev_data[$dkey][1] ) * 10 + 50  )+0.7;
                    }else{
                        $row["dev$dkey"] = ( ( $dev[$dkey] - $dev_data[$dkey][0] ) / $dev_data[$dkey][1] ) * 10 + 50;
                    }

                    if ( $row["dev$dkey"] >= 80 ) { $row["dev$dkey"] = 80; }
                    if ( $row["dev$dkey"] <= 20 ) { $row["dev$dkey"] = 20; }

                    // 比較用データを作成
                    $result_dev[$dkey] = $row["dev$dkey"];

                }
            }
            $dev_count++;
        }
        // 総合得点素点算出(おもみ付け)
        $all_score =
        (round($row['dev1'],1) * $row2['w1']) +
        (round($row['dev2'],1) * $row2['w2']) +
        (round($row['dev3'],1) * $row2['w3']) +
        (round($row['dev4'],1) * $row2['w4']) +
        (round($row['dev5'],1) * $row2['w5']) +
        (round($row['dev6'],1) * $row2['w6']) +
        (round($row['dev7'],1) * $row2['w7']) +
        (round($row['dev8'],1) * $row2['w8']) +
        (round($row['dev9'],1) * $row2['w9']) +
        (round($row['dev10'],1) * $row2['w10']) +
        (round($row['dev11'],1) * $row2['w11']) +
        (round($row['dev12'],1) * $row2['w12']);


        // 総合得点の偏差値算出

        if ($row2['sd'] > 0 ) {
            $standard_score = (( $all_score - $row2['ave'] ) / $row2['sd']) * 10 + 50;
        } else {
            $standard_score = 0;
        }
        if ( $standard_score >= 80 ) { $standard_score = 80; }
        if ( $standard_score <= 20 ) { $standard_score = 20; }

        $lv = '';

        if ( $standard_score <= 80 && $standard_score >= 65 ) { $lv = 5; }
        elseif ( $standard_score < 65 && $standard_score >= 55 ) { $lv = 4; }
        elseif ( $standard_score < 55 && $standard_score >= 45 ) { $lv = 3; }
        elseif ( $standard_score < 45 && $standard_score >= 35 ) { $lv = 2; }
        elseif ( $standard_score < 35 && $standard_score >= 20 ) { $lv = 1; }
        else { ; }

        $max_dev = max($result_dev);
        $dev_number = 0;
        for( $dcount = 1; $dcount <= 12; $dcount++ ) {
            if ( $row["dev$dcount"] == $max_dev && $dev_number == 0 ) {
                $dev_number = $dcount;
            }
        }

        return array($row,$lv,$standard_score,$dev_number);

    }

    function test()
    {
        $loginUser = auth()->user()->currentAccessToken();
        return response($loginUser, 200);
    }
}
