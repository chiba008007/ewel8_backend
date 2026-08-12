<?php

namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Http\Controllers\TestController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class BAJ4SpredSheetService
{
    public int $threeRow;
    public int $fourRow;
    public int $fiveRow;
    public int $maxCol;

    public $sheet;
    public $sheet1;

    public function __construct()
    {
        $this->threeRow = config('const.spreadsheet.rows.threeRow');
        $this->fourRow  = config('const.spreadsheet.rows.fourRow');
        $this->fiveRow  = config('const.spreadsheet.rows.fiveRow');
        $this->maxCol   = config('const.spreadsheet.maxCol.BAJ4.0');
    }

    public function createBody(
        $sheet,
        $sheet1,
        $codes,
        $value,
        $lastColIndex,
        $plus,
        $row
    ) {

        $this->sheet = $sheet;
        $this->sheet1 = $sheet1;
        // BAJ4
        // レベルを調べている
        $lv = "";
        $score = "";
        if ($codes[ 'BAJ4' ]->threeflag == 1) {
            list($lv, $score) = TestController::getStress2(
                $value->BAJ4->dev1,
                $value->BAJ4->dev2,
                $value->BAJ4->dev6,
            );
        } else {
            list($lv, $score) = TestController::getStress(
                $value->BAJ4->dev1,
                $value->BAJ4->dev2
            );
        }

        // レベル
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $lv ?? '');
        $this->setLevelColor($nextColLetter.$row, $lv);
        $plus++;
        // スコア
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $score ?? '');
        $sheet->duplicateStyle(clone $sheet1->getStyle('M7'), $nextColLetter.$row);
        $this->setScoreColor($nextColLetter.$row, $score);
        $plus++;

        // 適合レベル
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $value->BAJ4->level ?? '');
        $this->setLevelColor($nextColLetter.$row, $value->BAJ4->level);
        // 適合スコア
        $plus++;
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $value->BAJ4->score ?? '');
        $this->setScoreColor($nextColLetter.$row, $value->BAJ4->score);
        for ($i = 1;$i <= 12;$i++) {
            $field = 'dev' . $i;
            $plus++;
            $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
            $sheet->setCellValueExplicit(
                $nextColLetter . $row,
                sprintf('%.1f', (float)$value->BAJ4->$field),
                DataType::TYPE_STRING
            );
            // $sheet->duplicateStyle(clone $sheet1->getStyle('N7'), $nextColLetter.$row);
            $this->setScoreColor($nextColLetter.$row, $value->BAJ4->$field);
        }
        return $plus;
    }
    public function setLevelColor($row, $lv)
    {
        if ($lv === 1) {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('M8'), $row);
        } elseif ($lv === 2) {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L8'), $row);
        } else {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L7'), $row);
        }
    }
    public function setScoreColor($row, $score)
    {
        if ($score < 35) {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('M8'), $row);
        } elseif ($score < 45) {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L8'), $row);
        } else {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L7'), $row);
        }
    }
}
