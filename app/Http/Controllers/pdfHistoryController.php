<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pdf_history;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class pdfHistoryController extends Controller
{
    //
    public function index(Request $request)
    {
        Log::info('PDF出力ログ開始', [
           'user_id' => auth()->id(),
           'ip' => request()->ip(),
        ]);
        $start = microtime(true);
        $keyword = $request->customer_name;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $testname = $request->testname;

        $histories = pdf_history::query()
            ->join('exams', 'pdf_history.exam_id', '=', 'exams.id')
            ->join('tests', 'pdf_history.test_id', '=', 'tests.id')
            ->join('users as customer', 'exams.customer_id', '=', 'customer.id')
            ->join('users as partner', 'exams.partner_id', '=', 'partner.id')
            ->join('testpdfs', function ($join) {
                $join->on('pdf_history.test_id', '=', 'testpdfs.test_id')
                    ->where('testpdfs.status', '=', 1);
            })
            ->select(
                'pdf_history.id',
                'pdf_history.test_id',
                'pdf_history.ip',
                'exams.id as exam_id',
                'exams.email as exam_email',
                'tests.testname as testname',
                'customer.name as customer_name',
                'partner.name as partner_name',
                'testpdfs.pdf_id as pdf_id',
                'pdf_history.created_at',
                DB::raw("DATE_FORMAT(pdf_history.created_at, '%Y/%m/%d %H:%i:%s') as created_at_formatted")
            )
            ->when($keyword, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('customer.name', 'LIKE', "%{$keyword}%")
                    ->orWhere('partner.name', 'LIKE', "%{$keyword}%");
                });
            })
            ->when($testname, function ($query, $testname) {
                $query->where('tests.testname', 'LIKE', "%{$testname}%");
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pdf_history.created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]);
            })
            ->orderBy('pdf_history.created_at', 'desc')
            ->paginate($request->input('per_page', 5));

        Log::info('PDF出力ログ結果', [
            'user_id' => auth()->id(),
            'page' => $histories->currentPage(),
            'count' => $histories->count(),
            'total' => $histories->total(),
            'last_page' => $histories->lastPage(),
        ]);
        Log::info('PDF出力ログパフォーマンス', [
            'time' => microtime(true) - $start
        ]);

        return response($histories, 200);
    }
}
