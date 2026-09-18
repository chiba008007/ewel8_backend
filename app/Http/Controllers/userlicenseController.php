<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\userlisence;
use Illuminate\Support\Facades\DB;
use App\Models\ExamLog;

class userlicenseController extends Controller
{
    //
    public function list()
    {
        $results = UserLisence::with(['triggerHistories', 'examLogs'])
            ->get()
            ->groupBy('code')
            ->map(function ($group) {

                $first = $group->first();

                // 購入ライセンス
                $total_num = $group->sum('num');

                $code = $first->code;

                $tests = DB::table('tests')
                    ->join('testparts', 'testparts.test_id', '=', 'tests.id')
                    ->where('testparts.code', $code)
                    ->where('testparts.status', 1)
                    ->where('tests.status', 1)
                    ->select(
                        'tests.id',
                        'tests.testcount'
                    )
                    ->distinct()
                    ->get();

                $testIds = $tests->pluck('id');

                $exam_count = $tests->sum('testcount');

                $available_license = $total_num - $exam_count;

                $syori_count = DB::table('exams')
                    ->whereIn('test_id', $testIds)
                    ->whereNull('deleted_at')
                    ->whereNotNull('started_at')
                    ->count();

                $finished_count = DB::table('exams')
                    ->whereIn('test_id', $testIds)
                    ->whereNull('deleted_at')
                    ->whereNotNull('ended_at')
                    ->count();

                $zan = max($exam_count - $finished_count, 0);

                return (object)[
                    'code' => $first->code,
                    'total_num' => $total_num,
                    'available_license' => $available_license,
                    'exam_count' => $exam_count,
                    'syori_count' => $syori_count,
                    'zan' => $zan,
                ];
            })
            ->values();

        return response()->json([
            'result' => true,
            'message' => 'get lisence successfully',
            'data' => $results,
        ]);
    }
}
