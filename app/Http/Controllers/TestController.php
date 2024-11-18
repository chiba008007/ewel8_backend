<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\testparts;
use App\Models\testpdf;
use App\Models\User;
use App\Models\Exam;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends UserController
{

    private function checkuser($user_id){
        $loginUser = auth()->user()->currentAccessToken();
        $admin_id = $loginUser->tokenable->id;
        // 管理者でログインしたとき
        if($loginUser->tokenable->type == "admin"){
            $result = User::find($user_id)->where("admin_id",$admin_id)->count();
            if($result < 1){
                return false;
            }
            return true;
        }
        return false;
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
            if($loginUser->tokenable->type == "admin"){
                $result = User::find($user_id)->where("admin_id",$admin_id)->count();
                if($result < 1){
                    throw new Exception();
                }
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
                $pfs = "PFS";
                if($key === $pfs){
                    $params[ 'test_id' ] = $id;
                    $params[ 'code' ] = $pfs;
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

}
