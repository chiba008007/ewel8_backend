<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AdminPageLog;

class AdminPageLogController extends Controller
{
    private $limit = 200;

    public function getPageLog(Request $request)
    {

        $page = (int)$request->get("page", 1);
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
        ->map(function ($item) {
            $item->created_at_formatted = $item->created_at->format('Y-m-d H:xi:s');

            $item->partner_name = $item->user->company_name ?? null;
            $item->customer_name = $item->user->name ?? null;

            return $item;
        });

        $response = [
            'success' => true,
            "list" => $list,
            'offset' => $offset,
            'ceil' => $ceil,
        ];

        return response($response, 201);

    }
    //
    public function setPageLog(Request $request)
    {
        $user = Auth::user();

        $userType = $user instanceof \App\Models\Exam
            ? 'exam'
            : 'admin';

        $exists = AdminPageLog::where('user_id', $user->id)
            ->where('user_type', $userType)
            ->whereDate('created_at', now()->toDateString())
            ->where('path', $request->input('path'))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'duplicated' => true,
            ]);
        }

        AdminPageLog::create([
            'user_id'    => $user->id,
            'user_type'  => $userType,
            'route_name' => $request->input('route_name'),
            'title'      => $request->input('title'),
            'path'       => $request->input('path'),
            'params'     => json_encode($request->input('params', [])),
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true], 201);
    }

    public function setExamPageLog(Request $request)
    {
        $data = $request->validate([
            'route_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048',
                'regex:~^/exam(?:/|\?|$)~'],
            'params' => ['nullable', 'array'],
        ]);

        AdminPageLog::create([
            'user_id' => null,
            'user_type' => 'guest',
            'route_name' => $data['route_name'],
            'title' => $data['title'] ?? $data['route_name'],
            'path' => $data['path'],
            'params' => json_encode($data['params'] ?? []),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true], 201);
    }

}
