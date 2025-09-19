<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pdf_history;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class pdfHistoryController extends Controller
{
    //
    public function index(Request $request)
    {

        $keyword = $request->customer_name;
        $histories = pdf_history::select('id', 'exam_id', 'test_id')
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
        });

        if ($request->year && $request->month && $request->day) {
            // 年月日すべて指定 → その1日だけ
            $date = Carbon::create($request->year, $request->month, $request->day);
            $histories->whereDate('pdf_history.created_at', $date->toDateString());

        } elseif ($request->year && $request->month) {
            // 年月だけ指定 → その月全体
            $start = Carbon::create($request->year, $request->month, 1)->startOfDay();
            $end   = Carbon::create($request->year, $request->month, 1)->endOfMonth();
            $histories->whereBetween('pdf_history.created_at', [$start, $end]);

        } elseif ($request->year) {
            // 年だけ指定 → その年全体
            $start = Carbon::create($request->year, 1, 1)->startOfDay();
            $end   = Carbon::create($request->year, 12, 31)->endOfDay();
            $histories->whereBetween('pdf_history.created_at', [$start, $end]);
        }

        $histories = $histories->orderBy('pdf_history.created_at', 'desc')->get();

        return response($histories, 200);
    }
}
