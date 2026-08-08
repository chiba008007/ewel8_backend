<?php

namespace App\Services\Pdf;

use App\Libraries\Pfs;
use App\Libraries\Age;

class PdfPowerHarassmentHandler implements PdfHandlerInterface
{
    public $age;


    public function __construct(
        Age $age,
    ) {
        $this->age = $age;
    }

    /**
     * パワハラPDFを出力する
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
        // 元の pdf_id == 23 の処理をここへ移す

        $result = [];
        // 受検結果取得
        $pfsObj = new Pfs();
        $result = $pfsObj->getPfs($exam->id);
        // PFS結果がない場合は出力しない
        if ($result === null) {
            return ;
        }
        $risk = $pfsObj->getRiskPoint($result);
        // パワハラ用棒グラフ画像作成
        //require_once (public_path()."/PDF/pawaharaCreateGraph.php");
        $age = $this->age->getAge($result->starttime, $birth);
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
    }
}
