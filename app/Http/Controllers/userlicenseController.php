<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\userlisence;
use Illuminate\Support\Facades\DB;

class userlicenseController extends Controller
{
    //
    public function list()
    {
        $results = userlisence::with(['triggerHistories','examLogs'])
        ->select('code', DB::raw('SUM(num) as total_num'))
        ->groupBy('code')
        ->get()
        ->map(function ($item) {
            $add = $item->triggerHistories
                ->where('status', 'add')
                ->sum('num');

            $delete = $item->triggerHistories
                ->where('status', 'delete')
                ->sum('num');

            $item->exam_count = $add - $delete;
            $item->available_license = $item->total_num - $item->exam_count;

            // exam_logs の処理件数（status = 1 または 2）
            // Laravel の Eloquent は、リレーションを明示的に with() でロードしていなくても、
            // 初めてそのプロパティ（$item->examLogs）にアクセスした瞬間に
            // 自動でSQLを発行して取得してくれます。
            $item->syori_count = $item->examLogs
                ->whereIn('status', [1, 2])
                ->count();
            $item->zan = $item->exam_count - $item->syori_count;
            return $item;
        });

        return response()->json([
            'result' => true,
            'message' => 'get lisence successfully',
            'data' =>  $results,
        ]);
    }
}
