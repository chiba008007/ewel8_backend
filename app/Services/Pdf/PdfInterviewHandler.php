<?php

namespace App\Services\Pdf;

use App\Libraries\Baj3s;
use App\Libraries\LineBreak;
use App\Libraries\Age;

class PdfInterviewHandler implements PdfHandlerInterface
{
    public function __construct(
        private Age $age,
        private LineBreak $linebreak
    ) {
    }
    /**
     * 面接版PDFを出力する
     */
    public function handle(
        $pdf,
        object $value,
        object $exam,
        int $row,
        string $birth,
        ?string $pdfImagePath,
        int $id
    ): void {
        $baj3 = new Baj3s();
        $result = $baj3->getBaj3($exam->id);
        $strong = $baj3->getStrong($result, $value);
        $age = $this->age->getAge($result->starttime, $birth);

        // グラフ画像の保存先を作成する
        $fileDir = public_path("/images/PDF/{$id}/");

        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0775, true);
        }

        $filePath = $fileDir.date('YmdHis').'_radar_chart.png';
        createRadarChart($filePath, $result);

        $html = view('/PDF/INTERVIEWVALUE', [
            'row' => $row,
            'value' => $value,
            'exam' => $exam,
            'result' => $result,
            'age' => $age,
            'strong' => $strong,
            'pdfImagePath' => $pdfImagePath
                ? ltrim((string) parse_url($pdfImagePath, PHP_URL_PATH), '/')
                : '',
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
        ])->render();

        $pdf->SetAutoPageBreak(false);
        $pdf->WriteHTML($html);
        $pdf->Image($filePath, 50, 88, 0, 0);
        $pdf->Text(106, 114, '80');
        $pdf->Text(106, 121, '70');
        $pdf->Text(106, 128, '60');
        $pdf->Text(106, 133, '50');
        $pdf->Text(106, 141, '40');
        $pdf->Text(106, 147, '30');

    }
}
