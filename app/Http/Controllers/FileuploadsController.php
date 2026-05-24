<?php

namespace App\Http\Controllers;

use App\Models\fileuploads;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class FileuploadsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function list(Request $request)
    {
        try {
            // リクエスト値を取得
            $partner_id = $request->partner_id;
            $customer_id = $request->customer_id;

            Log::info('ファイル一覧取得開始', [
                'partner_id' => $partner_id,
                'customer_id' => $customer_id,
                'user_id' => auth()->id(),
            ]);

            // 顧客の利用権限チェック
            if (!$this->checkuser($customer_id)) {
                Log::warning('ファイル一覧取得 権限エラー', [
                    'customer_id' => $customer_id,
                    'user_id' => auth()->id(),
                ]);

                return response()->json(['message' => '権限がありません'], 403);
            }

            // ファイル一覧を取得
            $list = fileuploads::selectRaw('DATE_FORMAT(created_at, "%Y年%m月%d日") AS date')
                ->selectRaw('id, partner_id, admin_id, filename, filepath, size, openflag, status')
                ->where([
                    'customer_id' => $customer_id,
                    'partner_id' => $partner_id,
                    'status' => 1,
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('ファイル一覧取得成功', [
                'count' => $list->count(),
            ]);

            return response()->json($list, 200);

        } catch (\Throwable $e) {
            Log::error('ファイル一覧取得エラー', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['message' => 'サーバーエラー'], 500);
        }
    }

    public function openFlag(Request $request)
    {
        try {
            // ファイルを既読状態に更新
            $updatedCount = fileuploads::where([
                'id' => $request->id,
            ])->update(['openflag' => 1]);

            Log::info('ファイル既読フラグ更新', [
                'fileupload_id' => $request->id,
                'updated_count' => $updatedCount,
                'user_id' => auth()->id(),
            ]);

            return response()->json(true, 200);

        } catch (\Throwable $e) {
            Log::error('ファイル既読フラグ更新エラー', [
                'fileupload_id' => $request->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json(false, 500);
        }
    }
    public function deleteStatus(Request $request)
    {
        try {
            // ファイルを削除状態に更新
            $updatedCount = fileuploads::where([
                'id' => $request->id,
            ])->update(['status' => 0]);

            Log::info('ファイル削除ステータス更新', [
                'fileupload_id' => $request->id,
                'updated_count' => $updatedCount,
                'user_id' => auth()->id(),
            ]);

            return response()->json(true, 200);

        } catch (\Throwable $e) {
            Log::error('ファイル削除ステータス更新エラー', [
                'fileupload_id' => $request->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json(false, 500);
        }
    }
}
