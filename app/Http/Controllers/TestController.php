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

class TestController extends UserController
{

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
        return false;
    }

    public function getTest(Request $request){
        echo "test";
        exit();
    }
    public function getCsvList(Request $request){
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        try{
            if(!$this->checkuser($user_id)){
                throw new Exception();
            }
            $result = testparts::Where("test_id",$test_id)->get();
        }catch(Exception $e){
            return response([], 400);
        }
        return response($result, 200);
    }
    public function getQRParam(Request $request){
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        try{
            if(!$this->checkuser($user_id)){
                throw new Exception();
            }
            $result = Test::Where("id",$test_id)->where("user_id",$user_id)->first();
        }catch(Exception $e){
            return response([], 400);
        }
        return response($result, 200);
    }
    public function getQRLists(Request $request){
        $user_id = $request->user_id;
        $test_id = $request->test_id;

        try{
            if(!$this->checkuser($user_id)){
                throw new Exception();
            }
            $passwd = config('const.consts.PASSWORD');
            $result = Exam::Select("test_id","email","password","name")->where("test_id",$test_id)->where("deleted_at",null)->groupBy("test_id","email")->get();
            $list = [];
            $i = 0;
            foreach($result as $value){
                $pwd = openssl_decrypt($value[ 'password' ],'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                // 初期パスワードは空欄で表示
                $list[$i]['no'] = $i+1;
                $list[$i][ 'name'     ] = $value[ 'name' ];
                $list[$i][ 'exam_id'  ] = $value[ 'email' ];
                $list[$i][ 'password' ] = ($pwd == "password")?"":$pwd;
                $i++;
            }
        }catch(Exception $e){
            return response([], 400);
        }

        return response($list, 200);
    }
    public function getTestList(Request $request){
        $user_id = $request->user_id;
        try{
            if(!$this->checkuser($user_id)){
                throw new Exception();
            }
            $result = Test::Where("user_id",$user_id)->get();
        }catch(Exception $e){
            return response([], 400);
        }
        return response($result, 200);
    }

    public function getTestDetail(Request $request){
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        if($this->checkuser($user_id)){
            // PFSの受検者情報取得
            $rlt = [];
            $rlt['detail'] = Test::select(["tests.*","testparts.threeflag"])
            ->join("testparts","testparts.test_id","=","tests.id")
            ->where("tests.user_id",$user_id)
            ->where("tests.id",$test_id)
            ->first();
            $rlt['exams'] = Exam::select(["exams.*"])
            ->where("exams.test_id",$test_id)
            ->where("exams.customer_id",$user_id)
            ->where("exams.deleted_at","=",null)
            ->orderby("exams.id","ASC")
            ->get();
            $pfsArray = $this->getPFSDetail($test_id,$rlt['detail'][ 'threeflag' ]);
            $passwd = config('const.consts.PASSWORD');
            foreach($rlt[ 'exams' ] as $key=>$value)
            {
                $pwd = openssl_decrypt($value->password,'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                $rlt[ 'exams' ][$key]->birth = ($pwd === "password" || $pwd === "Test") ? "":$pwd;
                // PFSの受検者情報

                if(isset($pfsArray[$value->id])){
                    $rlt[ 'exams' ][$key]->endtime = (isset($pfsArray[$value->id]['endtime']))?$pfsArray[$value->id]['endtime']:'';
                    $rlt[ 'exams' ][$key]->level = (isset($pfsArray[$value->id]['level']))?$pfsArray[$value->id]['level']:'';
                    $rlt[ 'exams' ][$key]->lv = (isset($pfsArray[$value->id]['lv']))?$pfsArray[$value->id]['lv']:'';
                    $rlt[ 'exams' ][$key]->score = (isset($pfsArray[$value->id]['score']))?$pfsArray[$value->id]['score']:'';
                }
            }
            return response($rlt, 200);

        }else{
            return response([],400);
        }
    }

    private function getPFSDetail($test_id,$threeflag = 0){
        $sql = "
            SELECT
                exam_id,
                testparts_id,
                DATE_FORMAT(endtime,'%Y/%m/%d') as endtime,
                id,
                level,
                dev1,
                dev2,
                dev3,
                dev6
            FROM
                exampfses
            WHERE
                id IN
            (
                SELECT
                    MAX(id) as id
                FROM
                    exampfses
                WHERE
                    testparts_id=(SELECT id FROM testparts WHERE test_id = ?)
                GROUP BY exam_id,testparts_id
            )
            ";

        $pfsdetails = DB::select($sql, [$test_id]);
        $pfsArray = [];
        foreach($pfsdetails as $value){
            $pfsArray[$value->exam_id]['endtime'] = $value->endtime;
            $pfsArray[$value->exam_id]['level'] = $value->level;
            $pfsArray[$value->exam_id]['dev1'] = $value->dev1;
            $pfsArray[$value->exam_id]['dev2'] = $value->dev2;
            $pfsArray[$value->exam_id]['dev3'] = $value->dev3;
            $pfsArray[$value->exam_id]['dev6'] = $value->dev6;
            if($threeflag){
                list($lv, $score) = $this->getStress2($value->dev1, $value->dev2, $value->dev6);
            }else{
                list($lv, $score) = $this->getStress($value->dev1, $value->dev2);
            }
            $pfsArray[$value->exam_id]['lv'] = $lv;
            $pfsArray[$value->exam_id]['score'] = $score;
        }
        return $pfsArray;
    }


    //ストレスデータ取得ストレスフラグ無し
	public function getStress($dev1, $dev2) {
	  $ave = ($dev1 + $dev2) / 2;
	  $roundedAve = round($ave, 1);
	  if ($ave < 30) {
	    $st_level = 1;
	    $st_score = $roundedAve;
	  } else if ($ave < 35) {
	    if ($dev1 < 40 && $dev2 < 40) {
	      $st_level = 1;
	      $st_score = $roundedAve;
	    } else {
	      $st_level = 2;
	      $st_score = 35;
	    }
	  } else if ($ave < 40) {
	    if ($dev1 < 40 && $dev2 < 40) {
	      $st_level = 1;
	      $st_score = 34.9;
	    } else if ($dev1 < 30 || $dev2 < 30) {
	      $st_level = 2;
	      $st_score = $roundedAve;
	    } else {
	      $st_level = 3;
	      $st_score = 45;
	    }
	  } else if ($ave < 45) {
	    if ($dev1 < 30 || $dev2 < 30) {
	      $st_level = 2;
	      $st_score = $roundedAve;
	    } else if ($dev1 < 50 && $dev2 < 50) {
	      $st_level = 3;
	      $st_score = 45;
	    } else {
	      $st_level = 4;
	      $st_score = 55;
	    }
	  } else if ($ave < 50) {
	    if ($dev1 < 30 || $dev2 < 30) {
	      $st_level = 2;
	      $st_score = 44.9;
	    } else if ($dev1 < 50 && $dev2 < 50) {
	      $st_level = 3;
	      $st_score = $roundedAve;
	    } else {
	      $st_level = 4;
	      $st_score = 55;
	    }
	  } else if ($ave < 55) {
	    if ($dev1 < 30 || $dev2 < 30) {
	      $st_level = 2;
	      $st_score = 44.9;
	    } else {
	      $st_level = 4;
	      $st_score = 55;
	    }
	  } else if ($ave < 60) {
	    if ($dev1 < 50 || $dev2 < 50) {
	      $st_level = 4;
	      $st_score = $roundedAve;
	    } else if ($dev1 < 60 && $dev2 < 60) {
	      $st_level = 4;
	      $st_score = $roundedAve;
	    } else {
	      $st_level = 5;
	      $st_score = 65;
	    }
	  } else if ($ave < 65) {
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
	public function getStress2($dev1, $dev2,$dev3) {

		$dev1 = sprintf("%s",($dev1 >= 70 )?60:$dev1);
		$dev2 = sprintf("%s",($dev2 >= 70 )?60:$dev2);
		$dev3 = sprintf("%s",($dev3 >= 70 )?60:$dev3);

		$dev1 = sprintf("%s",($dev1 <= 35.21  )?20:$dev1);
		$dev2 = sprintf("%s",($dev2 <= 35.21  )?20:$dev2);
		$dev3 = sprintf("%s",($dev3 <= 35.21  )?20:$dev3);

		//ポジティブ思考力スコア反転
		$dev3 = 100-$dev3;

		$ave = ($dev1+$dev2+$dev3)/3;
		$st_score = round($ave,1);
		if($ave >= 64.79 ){
			$st_level = 5;
		}elseif( $ave >= 54.49){
			$st_level = 4;
		}elseif( $ave >= 45.3 ){
			$st_level = 3;
		}elseif( $ave >= 35 ){
			$st_level = 2;
		}else{
			$st_level = 1;
		}

		return array($st_level, $st_score);
	}

    // 重複を取り除く関数
    public function removeDuplicates($array) {
        // 配列の重複を削除
        return array_unique($array);
    }

    // 重複をチェックして必要な部分を再構築
    public function checkAndRebuild($array,$testcount) {
        $str = config('const.consts.alpha');
        // 重複している値を検出
        $duplicates = array_diff_assoc($array, array_unique($array));
        if (!empty($duplicates)) {
            // echo "重複があります。重複部分を作り直します。\n";
            // 重複部分を取り除いて新しい配列を作成
            $newArray = $this->removeDuplicates($array);
            while(true){
                foreach($duplicates as $key=>$value){
                    $newArray[$key] = substr(str_shuffle(str_repeat($str, 10)), 0, 3);
                }
                if($testcount == count($newArray)){
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
        $passwd = config('const.consts.PASSWORD');
        $str = config('const.consts.alpha');
        $lisence = config('const.consts.LISENCE');
        // 顧客用IDの作成
        $aExamid = [];
        for($i=0; $i < $request->testcount; $i++){
            $aExamid[] = substr(str_shuffle(str_repeat($str, 10)), 0, 3);
        }
        $aExamid = $this->checkAndRebuild($aExamid,$request->testcount);


        DB::beginTransaction();
        try{
            $user_id = $request->user_id;
            //所定のユーザーIDが利用可能かチェック
            $loginUser = auth()->user()->currentAccessToken();
            $admin_id = $loginUser->tokenable->id;
            // 管理者でログインしたとき

            // if($loginUser->tokenable->type == "admin"){
            //     $result = User::find($user_id)->where("admin_id",$admin_id)->count();
            //     if($result < 1){
            //         throw new Exception();
            //     }
            // }

            if(!$this->checkuser($user_id)){
                throw new Exception();
            }

            $params = [];
            $params["params"]=$query;
            $params["partner_id"]=$request->partner_id;
            $params["customer_id"]=$request->customer_id;
            $params["user_id"]=$request->user_id;
            $params["testname"]=$request->testname;
            $params["testcount"]=$request->testcount;
            $params["nameuseflag"]=$request->nameuseflag;
            $params["genderuseflag"]=$request->genderuseflag;
            $params["mailremaincount"]=$request->mailremaincount;
            $params["startdaytime"]=$request->startdaytime;
            $params["enddaytime"]=$request->enddaytime;
            $params["resultflag"]=$request->resultflag;
            $params["envcheckflag"]=$request->envcheckflag;
            $params["enqflag"]=$request->enqflag;
            $params["lisencedownloadflag"]=$request->lisencedownloadflag;
            $params["examlistdownloadflag"]=$request->examlistdownloadflag;
            $params["totaldownloadflag"]=$request->totaldownloadflag;
            $params["recomendflag"]=$request->recomendflag;
            $params["loginflag"]=$request->loginflag;
            $params["logintext"]=$request->logintext;
            $params["movietype"]=$request->movietype;
            $params["moviedisplayurl"]=$request->moviedisplayurl;
            $params["pdfuseflag"]=$request->pdfuseflag;
            $params["pdfstartday"]=$request->pdfstartday;
            $params["pdfendday"]=$request->pdfendday;
            $params["pdfcountflag"]=$request->pdfcountflag;
            $params["pdflimitcount"]=$request->pdflimitcount;
            $params["status"]=$request->status;
            $params["created_at"]=date("Y-m-d H:i:s");
            Test::insert($params);
            $id = DB::getPdo()->lastInsertId();
            $pdf = $request->pdf;
            foreach($pdf as $value){
                if($value['value']){
                    $params = [];
                    $params['test_id'] = $id;
                    $params['pdf_id'] = $value['key'];
                    $params[ 'status' ] = 1;
                    $params[ 'created_at'] = date("Y-m-d H:i:s");
                    if(!testpdf::insert($params)){
                        throw new Exception();
                    }
                }
            }
            $parts = $request->parts;
            $codePfs = $lisence[1]['list'][5]['code'];

            foreach($parts as $key=>$value){
                $params = [];
                if(isset($value[$codePfs]) &&  $value[$codePfs]){
                    $codePfs = $lisence[1]['list'][5]['code'];
                    $params[ 'test_id' ] = $id;
                    $params[ 'code' ] = $codePfs;
                    $params[ 'status' ] = $value[ $codePfs ][ 'status' ] ? 1:0;
                    $params[ 'threeflag' ] = $value[ $codePfs ][ 'threeflag' ] ? 1:0;
                    $params[ 'weightFlag' ] = $value[ $codePfs ][ 'weightFlag' ]? 1:0;
                    $params[ 'created_at' ] = date("Y-m-d H:i:s");
                    for($i=1;$i<=14;$i++){
                        $w = "weight".$i;
                        $params[$w] = (isset($value[$codePfs][ 'weight' ][$i]))?$value[$codePfs][ 'weight' ][$i]:0;
                    }
                    if(!testparts::insert($params)){
                        throw new Exception();
                    }
                    // テスト一覧修正
                    $lists = [];
                    $aChank = array_chunk($aExamid,100,true);
                    foreach($aChank as $cValue){
                        foreach($cValue as $cKey => $val){
                            $lists[$cKey][ 'test_id'     ] = $id;
                            $lists[$cKey][ 'partner_id'  ] = $request->partner_id;
                            $lists[$cKey][ 'customer_id' ] = $request->customer_id;
                            $lists[$cKey][ 'param'    ] = $query;
                            $lists[$cKey][ 'email'    ] = $val;
                            $lists[$cKey][ 'password' ] = openssl_encrypt('password', 'aes-256-cbc', $passwd[ 'key' ], 0, $passwd[ 'iv' ]);
                            $lists[$cKey][ 'type' ] = $codePfs;
                            $lists[$cKey][ 'created_at' ] = date('Y-m-d H:i:s');
                        }
                    }
                    if(!Exam::Insert($lists)){
                        throw new Exception();
                    }

                }
            }
            DB::commit();
            return response('success', 200);

        }catch(Exception $e){
            DB::rollBack();
            return response('error', 400);
        }

    }

    public function getTestTableTh(Request $request)
    {
        $user_id = $request->user_id;
        if(!$this->checkuser($user_id)){
            throw new Exception();
        }
        $test_id = $request->test_id;
        $data = Test::select("testparts.code")
            ->join("testparts","testparts.test_id","=","tests.id")
            ->where("tests.user_id",$user_id)
            ->where("testparts.test_id",$test_id)
            ->where("tests.status",1)
            ->where("testparts.status",1)
            ->get();

        return response($data , 200);
    }

    public function getPFSTestDetail(Request $request){

        $exam_id = $request->exam_id;
        $testparts_id = $request->testparts_id;

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
                    testparts_id=(SELECT id FROM testparts WHERE test_id = ?) AND
                    exam_id=?
                GROUP BY exam_id,testparts_id
            )
            ";

        $pfsdetails = DB::select($sql, [$testparts_id,$exam_id]);
        $ans_data = config('const.consts.PFS3');
        $result = $ans_data[$pfsdetails[0]->soyo];
        return response($result , 200);
    }

    function getSearchExam()
    {
       // $loginUser = auth()->user()->currentAccessToken();
        $data = Exam::select([
            'exams.*',
            'tests.testname as testname',
            "users_customer.name as customer_name",
            "users_partner.name as partner_name",
            "endtime"
        ])
        ->leftjoin("tests","tests.id","=","exams.test_id")
        ->leftjoin("users as users_customer","users_customer.id","=","exams.customer_id")
        ->leftjoin("users as users_partner","users_partner.id","=","exams.partner_id")
        ->leftjoin("exampfses",function ($join) {
            $join
            ->select("MAX(endtime)")
            ->on('exams.id','=','exampfses.exam_id')
            ->where('exampfses.status',"=",1);
        })
        ->where("exams.name","!=","''")
        ->ORDERBY("exampfses.endtime","DESC");
        return response($data->get(), 200);
    }

}
