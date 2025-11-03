<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\pdfs;
use Illuminate\Contracts\Encryption\DecryptException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class billController extends Controller
{
    //
    public function index(Request $request)
    {
        $query  = Bill::with('lists')
            ->orderBy('id', 'desc');
        if ($request->filled('bill_number')) {
            $query->where('bill_number', 'like', '%' . $request->bill_number . '%');
        }
        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }
        if ($request->filled('open_status') != "") {
            $query->where('open_status', $request->open_status);
        }
        if ($request->filled('pay_date')) {
            $query->where('pay_date', $request->pay_date);
        }
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        $bills = $query->get();
        return response()->json([
            'message' => 'success',
            'data' => $bills,
        ]);

    }

    public function get(Request $request)
    {
        $nextNumber = "";
        $id = $request->input('id');
        $data = [];
        // $idがあるとき編集用データを取得
        if ($id) {
            $data = Bill::with('lists')->where("id", $id)->first();
            $dataPost = explode("-", $data->post);
            $data->post1 = $dataPost[0];
            $data->post2 = $dataPost[1];

            $payDate = explode("-", explode(" ", $data->pay_date)[0]);
            $data->pay_date_y = $payDate[0];
            $data->pay_date_m = $payDate[1];
            $data->pay_date_d = $payDate[2];

            $billDate = explode("-", explode(" ", $data->bill_date)[0]);
            $data->bill_date_y = $billDate[0];
            $data->bill_date_m = $billDate[1];
            $data->bill_date_d = $billDate[2];

            $dataFromPost = explode("-", $data->from_post);
            $data->from_post1 = $dataFromPost[0];
            $data->from_post2 = $dataFromPost[1];

            $nextNumber = $data->bill_number;
        } else {
            $nextNumber = Bill::generateBillNumber();
        }
        // 適格請求書発行事業者番号
        $BUSSINESS_NUMBER = config('const.consts.BUSSINESS_NUMBER');
        return response()->json([
            'message' => 'Bill data successfully',
            'data' => $data,
            'nextNumber' => $nextNumber,
            'businessNumber' => $BUSSINESS_NUMBER
        ]);

    }
    public function set(Request $request)
    {
        $result = "fail";
        DB::transaction(function () use ($request, &$result, &$bill) {

            $id = $request->input('id');
            if ($id) {
                $result = "edit";
                // 既存の請求書を更新
                $bill = Bill::findOrFail($id);
                $bill->update($request->except('lists'));
                // 既存の明細を全削除
                $bill->lists()->delete();
            } else {
                $result = "new";
                // 請求書を作成
                $bill = Bill::create($request->except('lists')); // 明細部分は除外
            }
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
            'result' => $result,
            'message' => 'Bill created successfully',
            'data' => $bill->load('lists'),
        ]);
    }

    // 納品書
    public function slip($code)
    {
        $bill = Bill::with(['lists'])
                ->where('id', $code)
                ->first();
        //dd($bill->lists);
        $bill_date = explode("-", explode(" ", $bill->bill_date)[0]);
        $spreadsheet = IOFactory::load(storage_path('app/template_slip.xlsx'));
        $sheet = $spreadsheet->getActiveSheet();

        // セルに値を代入
        $sheet->setCellValue('A1', $bill->company_name);
        $sheet->setCellValue('O1', now()->format($bill_date[0].'年'.$bill_date[1].'月'.$bill_date[2].'日'));
        $sheet->setCellValue('K11', $bill->from_name);
        $sheet->setCellValue('L12', $bill->from_post);
        $sheet->setCellValue('K13', $bill->from_address_1);
        $sheet->setCellValue('K14', $bill->from_address_2);
        $sheet->setCellValue('L15', $bill->from_tel);
        $row = 19;
        $no = 1;
        foreach ($bill->lists as $value) {
            $sheet->setCellValue('A'.$row, $no);
            $sheet->setCellValue('C'.$row, $value->title);
            $sheet->setCellValue('I'.$row, $value->quantity.$value->unit."追加しました。");
            $row++;
            $no++;
        }

        // 一時ファイルとして保存
        $fileName = '納品書_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/temp/' . $fileName);


        // 保存ディレクトリがなければ作成
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);

    }

    // 請求書ダウンロード
    public function download($code)
    {
        $open_status = 1;
        $bill = Bill::find($code);

        $bill->open_status = $open_status;
        $bill->update_ts = now();
        $bill->save();


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
    public function delete(Request $request)
    {
        $id = $request->input('id');
        $status = 0;

        if (!$id) {
            return response()->json([
                'message' => 'Invalid request parameters',
            ], 400);
        }

        $bill = Bill::find($id);

        if (!$bill) {
            return response()->json([
                'message' => 'Bill not found',
            ], 404);
        }

        $bill->status = $status;
        $bill->update_ts = now();
        $bill->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $bill,
        ]);
    }
}
