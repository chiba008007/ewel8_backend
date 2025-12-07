<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamLoginHistory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamLoginHistoryController extends Controller
{
    // CSV出力用のカラム
    public $columns = [
        'アクセス時間',
        'パートナー名',
        '顧客名',
        'テスト名',
        '利用者名',
        '利用者ID',
        'プラットフォーム',
        'ブラウザ',
    ];

    private $limit = 200;
    //
    public function getData(Request $request){

        $offset = (int)$request->get("offset");
        $ceil = 0;

        // デフォルト値（必要に応じて調整）
        $limit  = $request->input('limit', $this->limit);
        $offset = $request->input('offset', $offset);

        // 最大取得件数を制限（安全のため）
       // $limit = min($limit, 500); // 500 以上は取得させない

        // 総件数を取得
        $total = ExamLoginHistory::count();

        // 最大ページ数（総ページ数）
        $ceil = (int) ceil($total / $limit);

        $query = ExamLoginHistory::with([
            'exam',
            'exam.test',
            'exam.customer',
            'exam.partner',
        ])
        ->orderBy('logged_in_at', 'desc')
        ->limit($limit)
        ->offset($offset)
        ->get();

        return response()->json([
            'data' => $query,
            'limit' => $limit,
            'ceil' => $ceil,
            'offset' => $offset,
        ]);
    }
    // CSVダウンロード
    public function download()
    {
        $lists = ExamLoginHistory::with([
            'exam',
            'exam.test',
            'exam.customer',
            'exam.partner',
        ])
        ->orderBy('logged_in_at', 'desc')
        ->get();

        $fileName = 'data_'.date('YmdHis').'.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($lists) {
            $handle = fopen('php://output', 'w');

            // Excelで文字化け防止（UTF-8 BOM）
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // ヘッダー行（定数配列を想定）
            fputcsv($handle, $this->columns);
            $num = 1;
            foreach ($lists as $list) {
                fputcsv($handle, array_merge([
                    $list->logged_in_at,
                    $list->partner_name,
                    $list->customer_name,
                    $list->test_name,
                    $list->name,
                    $list->email,
                    $list->platform,
                    $list->browser,
                ]));
                $num++;
            }

            fclose($handle);
        };
        return new StreamedResponse($callback, 200, $headers);
        exit();
    }
}
