<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AdminPageLog;

class AdminPageLogController extends Controller
{

    private $limit = 200;

    public function getPageLog(Request $request){

        $page = (int)$request->get("page",1);
        $ceil = 0;

        // デフォルト値（必要に応じて調整）
        $limit  = $request->input('limit', $this->limit);
        $offset = ($page - 1) * $limit;

        // 総件数を取得
        $total = AdminPageLog::count();

        // 最大ページ数（総ページ数）
        $ceil = (int) ceil($total / $limit);

        $list = AdminPageLog::with("user")
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->offset($offset)
        ->get()
        ->map(function($item){
            $item->created_at_formatted = $item->created_at->format('Y-m-d H:xi:s');

            $item->partner_name = $item->user->company_name ?? null;
            $item->customer_name = $item->user->name ?? null;

            return $item;
        });

        $response = [
            'success' => true,
            "list" =>$list,
            'offset' => $offset,
            'ceil' => $ceil,
        ];

        return response($response, 201);

    }
    //
    public function setPageLog(Request $request){

        $user = Auth::user(); // Sanctum 認証ユーザー

        $today = now()->format('Y-m-d');

        // すでに同じ人・同じ日・同じページのログがあるか？
        $exists = AdminPageLog::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->where('path', $request->path)
            ->exists();

        if ($exists) {
            // すでに記録済み → 新規作成しない
            return response(['success' => true, 'duplicated' => true], 200);
        }

        AdminPageLog::create([
            'user_id'    => $user ? $user->id : null,
            'route_name' => $request->route_name,
            'title'      => $request->title,
            'path'       => $request->path,
            'params'     => $request->params,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $response = [
            'success' => true,
        ];

        return response($response, 201);
    }
}
