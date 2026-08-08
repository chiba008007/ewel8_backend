<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Pfs;
use App\Libraries\Age;
use App\Libraries\PdfCountLimit;
use App\Libraries\LineBreak;
use App\Models\Exam;
use App\Models\pdf_history;
use App\Models\Test;
use App\Models\User;
use App\Services\Pdf\PdfInterviewHandler;
use App\Services\Pdf\PdfSelfUnderstandingHandler;
use App\Services\Pdf\PdfPowerHarassmentHandler;

class pdfs extends Model
{
    use HasFactory;

    public $pdf;
    public $pdfCountLimit;
    //
    public function __construct(
        $orientation = "P"
    ) {
        require_once(public_path()."/PDF/pfsCreateGraph.php");

        $pdf = new \Mpdf\Mpdf(
            [
            'mode' => 'ja', // 日本語モードを指定
            'format' => 'A4',
            'orientation' => $orientation,
            'margin_left' => 5,     // 左余白（mm）
            'margin_right' => 5,    // 右余白
            'margin_top' => 5,      // 上余白
            'margin_bottom' => 5,   // 下余白
            'margin_header' => 0,    // ヘッダー余白
            'margin_footer' => 0,    // フッター余白
            'fontDir' => [
                base_path('resources/fonts'),
                base_path('storage/fonts')
            ],
            'fontdata' => [
                'ipag' => [ // 既存
                    'R' => 'ipag.ttf',
                    'B' => 'ipag.ttf',
                ],
                'ipaexm' => [ // 明朝体
                    'R' => 'ipaexm.ttf',
                ],
            ],
            'default_font' => 'ipag', // デフォルトのフォントを設定
            ]
        );
        $this->pdf = $pdf;
        $this->pdfCountLimit = new PdfCountLimit();
    }

    // 証明書ダウンロード
    public function addCeartficateToPdf($id, $code, $birth)
    {
        $exam = Exam::where(["id" => $id])->first();
        $testname = $exam->test ? $exam->test->testname : null;
        $testname = $exam->test ? $exam->test->testname : null;
        $customerName = null;
        if ($exam->customer && $exam->customer->type === 'customer') {
            $customerName = $exam->customer->name;
        }
        $startdaytime = $exam->test->startdaytime;
        $number = $id."-".$code."-".strtotime($startdaytime);

        $pdf = $this->pdf;
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        $html = view('/PDF/CERTIFICATE', [
                'id' => $id,
                'email' => $code,
                'exam' => $exam,
                'testname' => $testname,
                'customerName' => $customerName,
                'number' => $number,
            ])->render();
        $pdf->WriteHTML($html);
        return $pdf;
    }

    public function addPageToPdf($id, $code, $birth)
    {
        $pdf = $this->pdf;
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage(); // ← ここで明示的にページ追加
        $passwd = config('const.consts.PASSWORD');
        $birth = preg_replace("/\-/", "/", $birth);
        $exam = Exam::where([
            ['id', '=', $id],
            ['email', '=', $code],
        ])->first();
        // PDFの出力数の上限チェック
        if (!$this->pdfCountLimit->pdfCountLimitCheck($exam[ 'test_id' ])) {
            echo "PDF出力制限数エラー";
            exit();
        }
        // pdfロゴパス取得
        $user = User::find($exam->partner_id);
        $pdfImagePath = $user->pdfImagePath;

        if (openssl_decrypt($exam->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']) != $birth) {
            echo "PDFの出力に失敗しました。";
            exit();
        }
        // pdf_historyにダウンロード実施したログを取得
        $this->pdf_history = new pdf_history();
        $this->pdf_history->test_id = $exam->test_id;
        $this->pdf_history->exam_id = $id;
        $this->pdf_history->ip = request()->ip();
        $this->pdf_history->save();

        // テストパターン
        $this->test = new Test();
        $pdflist = $this->test->getTestParts($exam->test_id);

        $this->age = new Age();
        $this->linebreak = new LineBreak();

        $handlers = [
            1  => PdfInterviewHandler::class,
            7  => PdfSelfUnderstandingHandler::class,
            23 => PdfPowerHarassmentHandler::class,
        ];
        $row = 0;
        foreach ($pdflist as $value) {
            if (!is_object($value)) {
                continue;
            }

            // PDF IDに対応するクラス名を取得する
            $handlerClass = $handlers[(int) $value->pdf_id] ?? null;

            if ($handlerClass === null) {
                continue;
            }

            // クラス名からインスタンスを生成する
            $handler = app()->make($handlerClass);

            // PDF固有処理を実行する
            $handler->handle(
                $pdf,
                $value,
                $exam,
                $row,
                $birth,
                $pdfImagePath,
                $id
            );
            $row++;
        }

        return $pdf;
    }
}
