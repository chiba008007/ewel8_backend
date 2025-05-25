<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Test;
use Illuminate\Http\Request;
use App\Libraries\Pfs;
use App\Libraries\Age;
use App\Libraries\LineBreak;

class indexController extends Controller
{
    public $linebreak;
    //
    public function index(Request $request, $id, $code, $birth)
    {

        $passwd = config('const.consts.PASSWORD');
        $birth = preg_replace("/\-/", "/", $birth);
        $exam = Exam::where([
            ['id', '=', $id],
            ['email', '=', $code],
        ])->first();
        if (openssl_decrypt($exam->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']) != $birth) {
            echo "PDFの出力に失敗しました。";
            exit();
        }
        // テストパターン
        $this->test = new Test();
        $pdflist = $this->test->getTestParts($exam->test_id);

        $this->age = new Age();
        $this->linebreak = new LineBreak();
        $pdf = new \Mpdf\Mpdf(
            [
            'mode' => 'ja', // 日本語モードを指定
            'format' => 'A4',
            'margin_left' => 5,     // 左余白（mm）
            'margin_right' => 5,    // 右余白
            'margin_top' => 5,      // 上余白
            'margin_bottom' => 5,   // 下余白
            'margin_header' => 0,    // ヘッダー余白
            'margin_footer' => 0,    // フッター余白
            'fontDir' => [base_path('resources/fonts')],
            'fontdata' => [
                'ipag' => [
                    'R' => 'ipag.ttf', // 日本語フォントを指定
                    'B' => 'ipag.ttf'
                ],
            ],
            'default_font' => 'ipag', // デフォルトのフォントを設定
            ]
        );

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
                require_once(public_path()."/PDF/pfsCreateGraph.php");
                $html = view(
                    '/PDF/JIKORIKAI',
                    [
                    'row' => $row,
                    'value' => $value,
                    'exam' => $exam,
                    'result' => $result,
                    'age' => $age,
                    'strong' => $strong,
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
                $pdf->SetMargins(0, 0, 0);
                $pdf->WriteHTML($html);
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
                    ])->render();
                $pdf->SetAutoPageBreak(false);
                $pdf->SetMargins(0, 0, 0);
                $pdf->WriteHTML($html);
                $row++;
            }
        }

        $filename = $code . "_" . date('Y') . date('m') . date('d') . ".pdf";
        return $pdf->Output($filename, 'D');

    }

}
