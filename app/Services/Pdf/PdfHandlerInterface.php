<?php

namespace App\Services\Pdf;

interface PdfHandlerInterface
{
    /**
     * PDFを出力する
     */
    public function handle(
        $pdf,
        object $value,
        object $exam,
        int $row,
        string $birth,
        string $pdfImagePath,
        int $id
    ): void;
}
