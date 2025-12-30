<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Pfs;
use App\Libraries\Age;
use App\Libraries\LineBreak;
use App\Models\Exam;
use App\Models\pdf_history;
use App\Models\Test;
use App\Models\User;

class pdfs extends Model
{
    use HasFactory;

    public $pdf;
    //
    public function __construct($orientation = "P")
    {
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
        $row = 0;
        foreach ($pdflist as $value) {
            if (is_object($value) && $value->pdf_id == 7) { // 自己理解版

                // 受検結果取得
                $pfsObj = new Pfs();
                $result = $pfsObj->getPfs($exam->id);
                // 強みを取得
                $strong = $pfsObj->getStrong($result, $value);
                $age = $this->age->getAge($result->starttime, $birth);
                // PFSグラフの画像作成
                // chartグラフのパス
                $fileDir = public_path()."/images/PDF/".$id."/";
                if (!file_exists($fileDir)) {
                    mkdir($fileDir);
                }
                $filePath = $fileDir.date('Ymdhis')."_radar_chart.png";
                //require_once(public_path()."/PDF/pfsCreateGraph.php");
                createRadarChart($filePath, $result);
                $html = view(
                    '/PDF/JIKORIKAI',
                    [
                    'row' => $row,
                    'value' => $value,
                    'exam' => $exam,
                    'result' => $result,
                    'age' => $age,
                    'strong' => $strong,
                    'pdfImagePath' => ltrim(parse_url($pdfImagePath, PHP_URL_PATH), '/'),
                    'element1' => $this->linebreak->insert_line_breaks($value->element1, 10),
                    'element2' => $this->linebreak->insert_line_breaks($value->element2, 10),
                    'element3' => $this->linebreak->insert_line_breaks($value->element3, 10),
                    'element4' => $this->linebreak->insert_line_breaks($value->element4, 10),
                    'element5' => $this->linebreak->insert_line_breaks($value->element5, 10),
                    'element6' => $this->linebreak->insert_line_breaks($value->element6, 10),
                    'element7' => $this->linebreak->insert_line_breaks($value->element7, 10),
                    'element8' => $this->linebreak->insert_line_breaks($value->element8, 10),
                    'element9' => $this->linebreak->insert_line_breaks($value->element9, 10),
                    'element10' => $this->linebreak->insert_line_breaks($value->element10, 10),
                    'element11' => $this->linebreak->insert_line_breaks($value->element11, 10),
                    'element12' => $this->linebreak->insert_line_breaks($value->element12, 10),
                    ]
                )->render();
                $pdf->SetAutoPageBreak(false);
                //$pdf->SetMargins(0, 0, 0);
                $pdf->WriteHTML($html);
                $pdf->Image(
                    $filePath,
                    50,    // X
                    88,   // Y
                    0,   // 幅
                    0      // 高さ（自動）
                );
                $pdf->Text(106, 114, '80');
                $pdf->Text(106, 121, '70');
                $pdf->Text(106, 128, '60');
                $pdf->Text(106, 133, '50');
                $pdf->Text(106, 141, '40');
                $pdf->Text(106, 147, '30');
                $row++;
            }

            if (is_object($value) && $value->pdf_id == 23) { // パワハラ
                $result = [];
                // 受検結果取得
                $pfsObj = new Pfs();
                $result = $pfsObj->getPfs($exam->id);
                $risk = $pfsObj->getRiskPoint($result);
                // パワハラ用棒グラフ画像作成
                //require_once (public_path()."/PDF/pawaharaCreateGraph.php");
                $html = view('/PDF/PAWAHARA', [
                    'row' => $row,
                    'value' => $value,
                    'exam' => $exam,
                    'result' => $result,
                    'age' => $age,
                    'risk' => $risk,
                    'pdfImagePath' => ltrim(parse_url($pdfImagePath, PHP_URL_PATH), '/'),
                    ])->render();
                $pdf->SetAutoPageBreak(false);
                //$pdf->SetMargins(0, 0, 0);
                $pdf->WriteHTML($html);
                $row++;
            }
        }

        return $pdf;
    }
}
