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
        $results = UserLisence::with(['triggerHistories', 'examLogs'])
        ->get()
        ->groupBy('code')
        ->map(function ($group) {
            $first = $group->first();

            $total_num = $group->sum('num');

            $add = $first->triggerHistories
                ->where('status', 'add')
                ->sum('num');

            $delete = $first->triggerHistories
                ->where('status', 'delete')
                ->sum('num');

            $exam_count = $add - $delete;
            $available_license = $total_num - $exam_count;

            $syori_count = $first->examLogs
                ->whereIn('status', [1, 2])
                ->count();

            $zan = $exam_count - $syori_count;

            return (object)[
                'code' => $first->code,
                'total_num' => $total_num,
                'exam_count' => $exam_count,
                'available_license' => $available_license,
                'syori_count' => $syori_count,
                'zan' => $zan,
            ];
        })
        ->values();

        return response()->json([
            'result' => true,
            'message' => 'get lisence successfully',
            'data' =>  $results,
        ]);
    }
}
