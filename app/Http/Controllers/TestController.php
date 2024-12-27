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
            $pfsArray = $this->getPFSDetail($test_id);

            $rlt['detail'] = Test::Where("user_id",$user_id)->where("id",$test_id)->first();
            $rlt['exams'] = Exam::where("exams.test_id",$test_id)
            ->where("exams.deleted_at","=",null)
            ->orderby("exams.id","ASC")
            ->get();
            $passwd = config('const.consts.PASSWORD');
            foreach($rlt[ 'exams' ] as $key=>$value)
            {
                $pwd = openssl_decrypt($value->password,'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                $rlt[ 'exams' ][$key]->birth = ($pwd === "password" || $pwd === "Test") ? "":$pwd;
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

    private function getPFSDetail($test_id){
        $sql = "
            SELECT
                exam_id,
                testparts_id,
                DATE_FORMAT(endtime,'%Y/%m/%d') as endtime,
                id,
                level,
                dev1,
                dev2,
                dev3
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
                    testparts_id=?
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
            list($lv, $score) = $this->getStress($value->dev1, $value->dev2);
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


    public function setTest(Request $request)
    {
        $query = substr(bin2hex(random_bytes(8)), 0, 8);
        $passwd = config('const.consts.PASSWORD');
        $str = config('const.consts.alpha');

        DB::beginTransaction();
        try{
            $user_id = $request->user_id;
            //所定のユーザーIDが利用可能かチェック
            $loginUser = auth()->user()->currentAccessToken();
            $admin_id = $loginUser->tokenable->id;
            // 管理者でログインしたとき
            /*
            if($loginUser->tokenable->type == "admin"){
                $result = User::find($user_id)->where("admin_id",$admin_id)->count();
                if($result < 1){
                    throw new Exception();
                }
            }
                */
            if(!$this->checkuser($user_id)){
                throw new Exception();
            }

            $params = [];
            $params["params"]=$query;
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
            foreach($parts as $key=>$value){
                $params = [];
                if($key === "PFS"){
                    $params[ 'test_id' ] = $id;
                    $params[ 'code' ] = "PFS";
                    $params[ 'status' ] = $value[ 'status' ] ? 1:0;
                    $params[ 'threeflag' ] = $value[ 'threeflag' ] ? 1:0;
                    $params[ 'weightFlag' ] = $value[ 'weightFlag' ]? 1:0;
                    $params[ 'created_at' ] = date("Y-m-d H:i:s");
                    for($i=1;$i<=14;$i++){
                        $w = "weight".$i;
                        $params[$w] = (isset($value[ 'weight' ][$i]))?$value[ 'weight' ][$i]:0;
                    }

                }

                if(!testparts::insert($params)){
                    throw new Exception();
                }

                // テスト一覧修正
                $params = [];
                for($i=0;$i<$request->testcount;$i++){
                    $params[$i][ 'test_id'  ] = $id;
                    $params[$i][ 'param'    ] = $query;
                    $params[$i][ 'email'    ] = substr(str_shuffle(str_repeat($str, 10)), 0, 3);
                    $params[$i][ 'password' ] = openssl_encrypt('password', 'aes-256-cbc', $passwd[ 'key' ], 0, $passwd[ 'iv' ]);
                    $params[$i][ 'type' ] = $key;
                    $params[$i][ 'created_at' ] = date('Y-m-d H:i:s');
                }
                if(!Exam::insert($params)){
                    throw new Exception();
                }
            }

            DB::commit();
            return response($parts, 200);
        }catch(Exception $e){
            DB::rollBack();
            return response([], 400);
        }
        return response(true, 200);
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
}
