<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\pdfs;
use Illuminate\Contracts\Encryption\DecryptException;

class billController extends Controller
{
    //
    public function index(Request $request)
    {
        $bills = Bill::with('lists')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'message' => 'success',
            'data' => $bills,
        ]);

    }

    public function get(Request $request)
    {
        $nextNumber = Bill::generateBillNumber();
        $BUSSINESS_NUMBER = config('const.consts.BUSSINESS_NUMBER');
        $data = [];
        return response()->json([
            'message' => 'Bill data successfully',
            'data' => $data,
            'nextNumber' => $nextNumber,
            'businessNumber' => $BUSSINESS_NUMBER
        ]);

    }
    public function set(Request $request)
    {

        DB::transaction(function () use ($request, &$bill) {

            // 請求書を作成
            $bill = Bill::create($request->except('lists')); // 明細部分は除外

            // 明細データがあれば登録
            if ($request->has('lists') && is_array($request->lists)) {
                $lists = collect($request->lists)->map(function ($item, $index) {
                    return [
                        'number'     => $item['number']     ?? $index + 1,
                        'title'      => $item['title']      ?? '',
                        'name'       => $item['name']       ?? '',
                        'kikaku'     => $item['kikaku']     ?? '',
                        'quantity'   => $item['quantity']   ?? 0,
                        'unit'       => $item['unit']       ?? '',
                        'money'      => $item['money']      ?? 0,
                        'create_ts'  => now(),
                        'update_ts'  => now(),
                    ];
                })->toArray();

                $bill->lists()->createMany($lists);
            }
        });

        return response()->json([
            'message' => 'Bill created successfully',
            'data' => $bill->load('lists'),
        ]);
    }

    // 請求書ダウンロード
    public function download($code)
    {
        $tax = config('const.consts.TAX');
        Log::info('bill@index called', compact('code'));
        try {
            // データの取得
            $bill = Bill::with(['lists'])
                ->where('id', $code)
                ->first();
            $billNumber = $bill->bill_number;
            $count = $bill->lists->count();
            // 税抜き金額の計算
            $exTotal = $bill->lists->sum(fn ($item) => $item->quantity * $item->money);
            // 税金分
            $taxTotal = floor($exTotal * ($tax / 100));
            // 合計
            $total = $exTotal + $taxTotal;

            // pdfsサービスを利用
            $pdfService = new pdfs(); // ← あなたのクラスのコンストラクタが呼ばれる
            // 空のページを追加
            $pdf = $pdfService->pdf; // mPDFインスタンス
            $pdf->AddPage();
            $html = view(
                '/PDF/BILL',
                [
                    'bill' => $bill,
                    'exTotal' => $exTotal,
                    'taxTotal' => $taxTotal,
                    'total' => $total,
                    'tax' => $tax,
                    'minRows' => 8, // 最低の行数
                    'extraRows' => 2, // 補足分の空行
                    'rowCount' => $count,
                ]
            );
            $pdf->SetFont('ipaexm');
            $pdf->WriteHTML($html);

            // ファイル名を生成
            $filename = sprintf('%s_%s.pdf', $billNumber, now()->format('Ymd'));
            $pdf->Output($filename, 'D');

            // LaravelのレスポンスとしてPDFを出力
            // return response()->streamDownload(
            //     fn () => print($pdf->Output('', 'D')), // 'S'：PDFを文字列で返す
            //     $filename,
            //     ['Content-Type' => 'application/pdf']
            // );

        } catch (DecryptException $e) {
            Log::error('DecryptException: '.$e->getMessage());
            abort(400, 'トークン復号エラー'); // ← 一旦 400 に
        } catch (\Throwable $e) {
            Log::error('PDF@index failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'PDF生成エラー');
        }

    }

}
