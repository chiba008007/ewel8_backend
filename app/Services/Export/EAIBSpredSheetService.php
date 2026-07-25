<?php

namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EAIBSpredSheetService
{
    /**
     * EAIbのタイトルを出力する
     */
    public function createTitle($sheet, $sheet1, $lastColIndex, $code): void
    {
        // EAIbの開始列と終了列を取得（6項目）
        $start = Coordinate::stringFromColumnIndex($lastColIndex + 1);
        $emotionEnd = Coordinate::stringFromColumnIndex($lastColIndex + 5);
        $infoColumn = Coordinate::stringFromColumnIndex($lastColIndex + 6);

        // 感情能力：3行目、5列結合
        $emotionRange = "{$start}3:{$emotionEnd}3";
        $sheet->mergeCells($emotionRange);
        $sheet->setCellValue("{$start}3", '感情能力');
        $sheet->duplicateStyle(
            clone $sheet1->getStyle('L3:P3'),
            $emotionRange
        );


        // 各項目：4～5行を縦結合
        $titles = ['総合', '読み取り力', '理解力', '選択力', '切り替え力'];

        foreach ($titles as $index => $title) {
            $column = Coordinate::stringFromColumnIndex(
                $lastColIndex + $index + 1
            );
            $range = "{$column}4:{$column}5";

            $sheet->mergeCells($range);
            $sheet->setCellValue("{$column}4", $title);
            $sheet->duplicateStyle(
                clone $sheet1->getStyle('L4:L5'),
                $range
            );
        }

        // 情報の捉え方：3～5行を縦結合
        $infoRange = "{$infoColumn}3:{$infoColumn}5";
        $sheet->mergeCells($infoRange);
        $sheet->setCellValue("{$infoColumn}3", '情報の捉え方');
        $sheet->duplicateStyle(
            clone $sheet1->getStyle('L3:L5'),
            $infoRange
        );
        // 右側の罫線だけ太くする
        $sheet->getStyle($infoRange)
            ->getBorders()
            ->getRight()
            ->setBorderStyle(Border::BORDER_MEDIUM);
        // 各小見出しの下線を二重線にする
        foreach ($titles as $index => $title) {
            $column = Coordinate::stringFromColumnIndex(
                $lastColIndex + $index + 1
            );

            $sheet->getStyle("{$column}4:{$column}5")
                ->getBorders()
                ->getBottom()
                ->setBorderStyle(Border::BORDER_DOUBLE);
        }

        // 情報の捉え方も下線を二重線にする
        $sheet->getStyle($infoRange)
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE);
    }

    /**
     * EAIbの結果を出力する
     */
    public function createBody(
        $sheet,
        $sheet1,
        $codes,
        $value,
        $lastColIndex,
        $plus,
        $row
    ): int {
        // EAIbの集計結果
        $fields = [
            'sougo',
            'yomitori',
            'rikai',
            'sentaku',
            'kirikae',
            'jyoho',
        ];

        foreach ($fields as $field) {
            $column = Coordinate::stringFromColumnIndex(
                $lastColIndex + $plus
            );
            $cell = $column . $row;

            // 0 または未設定は空欄
            $valueData = $value->EAIb->$field ?? null;
            $displayValue = ((float) $valueData === 0.0)
                ? ''
                : number_format((float) $valueData, 1, '.', '');

            $sheet->setCellValueExplicit(
                $cell,
                $displayValue,
                DataType::TYPE_STRING
            );

            // 空欄でも罫線を残す
            $sheet->duplicateStyle(
                clone $sheet1->getStyle('L7'),
                $cell
            );

            $plus++;
        }

        return $plus;

    }
}
