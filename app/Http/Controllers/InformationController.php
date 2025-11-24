<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class InformationController extends Controller
{
    //
    public function getInfoList()
    {
        $informations = Information::where('status', 1)
        ->with('viewers')
        ->get();
        return response()->json($informations);
    }

    public function editInfoListDelete(Request $request){
        try {
            $paramId = $request->get("id");
            $paramIds = $request->get("ids");
            $data = ['status' => 0];
            if (is_array($paramIds) && count($paramIds) > 0) {
                foreach ($paramIds as $id) {
                    $info = Information::find($id);
                    if ($info) {
                        $info->update($data);
                    }
                }
            } else {
                $info = Information::find($paramId);
                $info->update($data);
            }
            return response()->json([
                'status' => true,
                'info' => $info,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
            ]);
        }
    }

    public function setInfoList(Request $request)
    {
        $paramId = $request->get("id");
        try {
            DB::beginTransaction();
            $filed = $this->uploadFile($request);

            if($paramId ){
                // 既存レコード取得
                $info = Information::find($paramId);
                if ($info) {
                    $data = [
                        'title'      => $request->title,
                        'started_at' => $request->started_at,
                        'ended_at'   => $request->ended_at,
                        'display'    => $request->display,
                        'note'       => $request->note,
                    ];

                    // delFlag → file 削除
                    if ($request->get("delFlag")) {
                        $data['file'] = "";
                    }

                    // 新しいファイルがあるときだけ更新
                    if (!empty($filed) && !$request->get("delFlag")) {
                        $data['file'] = $filed;
                    }

                    $info->update($data);

                    DB::table('information_user')
                        ->where('information_id', $paramId)
                        ->delete();
                }
            }else{
                $info = Information::create([
                    'title'      => $request->title,
                    'started_at' => $request->started_at,
                    'ended_at'   => $request->ended_at,
                    'display'    => $request->display,
                    'note'       => $request->note,
                    'file'       => $filed,
                ]);
            }

            if ($request->has('users')) {
                $users = json_decode($request->users, true);
                // user_id の配列を pivot 用に整形
                $pivotData = collect($users)->mapWithKeys(function ($item) {
                    return [
                        $item['user_id'] => []  // status = 有効
                    ];
                })->toArray();

                // pivot へ保存
                $info->viewers()->syncWithoutDetaching($pivotData);

            }
            DB::commit();
            return response()->json([
                'status' => true,
                'file' => $filed
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'file' => "",
                'error' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
            ]);
        }
    }

    private function uploadFile($request){

        $file = $request->file('file');
        if($file){
            // 保存先
            $uploadDir = public_path('storage/uploads');

            // 元ファイルの拡張子だけ取得
            $extension = $file->getClientOriginalExtension();

            // ランダム32文字 + 拡張子
            $originalName = $file->getClientOriginalName();
            $safeName = str_replace(['/', '\\'], '_', $originalName);

            $timestamp = date('Ymd_His');
            $saveName = $timestamp . '_' . $safeName;

            // 保存
            $file->storeAs('public/uploads', $saveName);
            return asset("storage/uploads/{$saveName}");
        }
        return "";
    }

    public function getUser(Request $request){
        $users = User::whereNull('deleted_at')->where("partner_id",0)->get();

        $information = "";
        $id = $request->get("id");
        if($id){
            $information = Information::where('status', 1)
            ->where("id",$id)
            ->with('viewers')
            ->first();
        }
        return response()->json([
                'users' => $users,
                'information' => $information,
                'status' => true,
        ]);
    }

    public function getInformation(Request $request){
        $userId = $request->get("id");

        $user = User::find($userId);
        $now = now();

        $query = Information::query()
            ->where('status', 1)
            ->where('started_at', '<=', $now)
            ->where('ended_at', '>=', $now);

             // display条件
        $query->where(function ($q) use ($user) {

            // 1: 全体
            $q->orWhere('display', 1);

            // 2: 代理店
            if ($user->type === 'partner') {
                $q->orWhere('display', 2);
            }

            // 3: 顧客
            if ($user->type === 'customer') {
                $q->orWhere('display', 3);
            }

            // 4: 個別
            $q->orWhere(function ($sq) use ($user) {
                $sq->where('display', 4)
                    ->whereHas('informationUsers', function ($u) use ($user) {
                        $u->where('user_id', $user->id);
                    });
            });
        });

        $informations = $query->orderBy('started_at', 'DESC')->get();

        return response()->json([
            'user' => $user,
            'informations' => $informations,
        ]);

    }

}
