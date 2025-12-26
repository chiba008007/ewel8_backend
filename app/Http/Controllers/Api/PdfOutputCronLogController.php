<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PdfOutputCronLog;

class PdfOutputCronLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function set(Request $request)
    {

        DB::transaction(function () use ($request) {

            PdfOutputCronLog::create([
                'partner_id'      => $request->partner_id,
                'customer_id'     => $request->customer_id,
                'test_id'         => $request->test_id,
                'type'            => 'individual', // individual / merged
                'total_count'     => 0,               // 後続Jobで確定
                'processed_count' => 0,
                'status'          => 'pending',
                'file_path'       => null,
                'error_message'   => null,
            ]);

        });

        return response()->json([
            'message' => 'PDF出力ジョブを登録しました',
        ], 201);

    }

}
