<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\pdfDownloads;
use Illuminate\Http\Request;
use Exception;

class PdfDownloadController extends Controller
{
    //
    public function index()
    {
        // apiから実施
        // pdf一括の実行予定を保存する
        return response(true, 200);
    }
    public function getPDFUpload(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        //  $user_id = $loginUser->tokenable->id;
        // if (!$this->checkuser($user_id)) {
        //     throw new Exception();
        // }
        // $user = User::select(["partner_id"])->find($request->customer_id);
        $list = pdfDownloads::selectRaw('
                id,
                partner_id,
                customer_id,
                test_id,
                status,
                type,
                code,
                DATE_FORMAT(created_at, "%Y年%m月%d日") as start,
                DATE_FORMAT(updated_at, "%Y年%m月%d日") as end
            ')
            ->where([
                'partner_id'  => $request->partner_id,
                'customer_id' => $request->customer_id,
                'test_id'     => $request->test_id,
            ])
            ->orderBy('id')
            ->get();
        return response($list, 200);

    }
    public function setPDFUpload(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $user_id = $loginUser->tokenable->id;
        if (!$this->checkuser($user_id)) {
            throw new Exception();
        }
        // パートナーIDの取得
        try {
            $user = User::select(["partner_id"])->find($request->customer_id);
            pdfDownloads::create([
                'partner_id'  => $user->partner_id,
                'customer_id' => $request->customer_id,
                'test_id'     => $request->test_id,
                'admin_id'    => $loginUser->tokenable->id,
                'type'        => $request->type,
                'code'        => $request->code,
            ]);

            return response(true, 200);
        } catch (Exception $e) {
            return false;
        }
    }
}
