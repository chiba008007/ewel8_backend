<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Weight;
use App\Models\User;

class WeightController extends Controller
{
    //
    function setWeightMaster(Request $request){
        try{
            $customer_id = $request->id;
            // 顧客情報の取得
            $customer = $this->getPartnerId($customer_id);
            $set = [];
            $set[ 'customer_id' ] = $customer_id;
            $set[ 'partner_id'  ] = $customer->partner_id;
            $set[ 'name'  ] = $request->name;
            for($i=1;$i<=12;$i++){
                $set[ 'wt'.$i  ] = is_numeric($request['wt'.$i])?$request['wt'.$i]:0;
            }
            $set[ 'ave'   ] = is_numeric($request->ave)?$request->ave:0;
            $set[ 'hensa' ] = is_numeric($request->hensa)?$request->hensa:0;
            $set[ 'created_at' ] = date("Y-m-d H:i:s");
            Weight::insert($set);
            return response(true, 200);
        }catch(\Exception $e){
            return response(false, 201);
        }
    }
    public function getPartnerId($customer_id){
        $this->checkuser($customer_id);
        $loginUser = auth()->user()->currentAccessToken();
        $customer = User::where("type","customer")
            ->where('id',$customer_id)
            ->where('deleted_at',null);
        $customer->where("admin_id",$this->admin_id);
        $customer = $customer->first();
        return $customer;
    }
    function editWeightMaster(Request $request){
        try{
            $weightid = $request->weightid;
            $customer_id = $request->id;
            // 顧客情報の取得
            $customer = $this->getPartnerId($customer_id);
            if($customer){
                Weight::where([
                    'id'=>$weightid,
                    'customer_id'=>$customer_id
                ])->update([
                    'wt1'=>$request->wt1,
                    'wt2'=>$request->wt2,
                    'wt3'=>$request->wt3,
                    'wt4'=>$request->wt4,
                    'wt5'=>$request->wt5,
                    'wt6'=>$request->wt6,
                    'wt7'=>$request->wt7,
                    'wt8'=>$request->wt8,
                    'wt9'=>$request->wt9,
                    'wt10'=>$request->wt10,
                    'wt11'=>$request->wt11,
                    'wt12'=>$request->wt12,
                    'ave'=>$request->ave,
                    'hensa'=>$request->hensa,
                    'name'=>$request->name,
                ]);
            }else{
                 throw new Exception("エラーが発生しました");
            }

            return response(true, 200);
        }catch(\Exception $e){
            return response(false, 201);
        }
    }
    function editStatusWeightMaster(Request $request){
        try{
            $weightid = $request->weightid;
            $customer_id = $request->id;
            // 顧客情報の取得
            $customer = $this->getPartnerId($customer_id);
            if($customer){
                Weight::where([
                    'id'=>$weightid,
                    'customer_id'=>$customer_id
                ])->update([
                    'status'=>0
                ]);
            }else{
                 throw new Exception("エラーが発生しました");
            }

            return response(true, 200);
        }catch(\Exception $e){
            return response(false, 201);
        }
    }
    function getWeightMasterDetail(Request $request){
        $weightid = $request->weightid;
        $customer_id = $request->customer_id;
        $customer = $this->getPartnerId($customer_id);

        $weight = Weight::where([
            "id"=>$weightid,
            "partner_id"=>$customer->partner_id,
            "status"=>1
        ])
        ->first();

        return response($weight, 200);

    }

    function getWeightMaster(Request $request)
    {
        $customer_id = $request->id;
        $customer = $this->getPartnerId($customer_id);
        $user = Weight::select([
            'id',
            'name',
            'created_at',
        ])
        ->selectRaw('DATE_FORMAT(created_at, "%Y/%m/%d %H:%i") AS date')
        ->where([
            "customer_id"=>$customer_id,
            "partner_id"=>$customer->partner_id,
            "status"=>1
        ])
        ->orderBy('id', 'desc')
        ->get();
        return response($user, 200);
    }
}
