<?php
namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Http\Controllers\TestController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PFSSpredSheetService
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
      $this->maxCol   = config('const.spreadsheet.maxCol.PFS.0');
  }

  public function createTitle($sheet,$sheet1,$lastColIndex,$code){
    $threeRow = $this->threeRow;
    $fourRow = $this->fourRow;
    $fiveRow = $this->fiveRow;
    $maxCol = $this->maxCol;

    // ここからデータのタイトル開始
    $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + 1);

    $endColLetter = Coordinate::stringFromColumnIndex(
        $lastColIndex + $maxCol
    );
    $sheet->mergeCells($nextColLetter.$threeRow .':'.$endColLetter.$threeRow);
    $sheet->duplicateStyle( $sheet1->getStyle('L3:AA3'),'L3:AA3');

    // 幅を10にする
    $startIndex = Coordinate::columnIndexFromString($nextColLetter);
    $endIndex   = Coordinate::columnIndexFromString($endColLetter);
    for ($i = $startIndex; $i <= $endIndex; $i++) {
        $colLetter = Coordinate::stringFromColumnIndex($i);
        $sheet->getColumnDimension($colLetter)->setWidth(10);
    }

    // ストレス共生
    $nextColLetter_plus2 = Coordinate::stringFromColumnIndex(
        $lastColIndex + 2
    );
    $range = $nextColLetter.$fourRow.':'.$nextColLetter_plus2.$fourRow;
    $sheet->mergeCells($nextColLetter.$fourRow.':'.$nextColLetter_plus2.$fourRow);
    $sheet->duplicateStyle($sheet1->getStyle('L4:M4'),$range);
    $sheet->setCellValue($nextColLetter.$fourRow, 'ストレス共生');

    // 線を引く
    $sheet->getStyle($nextColLetter_plus2.$fourRow)
        ->getBorders()
        ->getRight()
        ->setBorderStyle(Border::BORDER_THIN);

    $cell = $nextColLetter . $fiveRow;
    $sheet->setCellValue($cell, 'レベル');
    $sheet->duplicateStyle($sheet1->getStyle('L5'),'L5');
    $cell = $nextColLetter_plus2 . $fiveRow;
    $sheet->setCellValue($cell, 'スコア');
    $sheet->duplicateStyle($sheet1->getStyle('M5'),'M5');

    // 適合レベル
    $nextColLetter_plus3 = Coordinate::stringFromColumnIndex(
        $lastColIndex + 3
    );
    $range = $nextColLetter_plus3.$fourRow.':'.$nextColLetter_plus3.$fiveRow;
    $sheet->mergeCells($range);
    $sheet->setCellValue($nextColLetter_plus3.$fourRow, '適合レベル');
    // マージ範囲にそのまま適用
    $sheet->duplicateStyle($sheet1->getStyle('N4:N5'),$range);

    // 適合スコア
    $nextColLetter_plus4 = Coordinate::stringFromColumnIndex(
        $lastColIndex + 4
    );
    $range = $nextColLetter_plus4.$fourRow.':'.$nextColLetter_plus4.$fiveRow;
    $sheet->mergeCells($range);
    $sheet->setCellValue($nextColLetter_plus4.$fourRow, '適合スコア');
    // マージ範囲にそのまま適用
    $sheet->duplicateStyle($sheet1->getStyle('N4:N5'),$range);

    // 自分を適切に認識できているか
    $nextColLetter_plus5 = Coordinate::stringFromColumnIndex($lastColIndex + 5);
    $nextColLetter_plus6 = Coordinate::stringFromColumnIndex($lastColIndex + 6);
    $nextColLetter_plus7 = Coordinate::stringFromColumnIndex($lastColIndex + 7);
    $range = $nextColLetter_plus5.$fourRow.':'.$nextColLetter_plus7.$fourRow;
    $sheet->setCellValue($nextColLetter_plus5.$fourRow, '自分を適切に認識できているか');
    // マージ範囲にそのまま適用
    $sheet->duplicateStyle($sheet1->getStyle('P4:R4'),$range);
    $sheet->mergeCells($range);
    // 自己感情モニタリング力自分の感情を認識できるか
    $sheet->setCellValue($nextColLetter_plus5.$fiveRow, '自己感情モニタリング力自分の感情を認識できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus5.$fiveRow);
    if($code->weight1) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus5.$fiveRow);
    // 客観的自己評価力自分を客観的に評価できるか
    $sheet->setCellValue($nextColLetter_plus6.$fiveRow, '客観的自己評価力自分を客観的に評価できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus6.$fiveRow);
    if($code->weight2) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus6.$fiveRow);
    // 自己肯定力自分を価値ある存在として評価できるか
    $sheet->setCellValue($nextColLetter_plus7.$fiveRow, '自己肯定力自分を価値ある存在として評価できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus7.$fiveRow);
    if($code->weight3) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus7.$fiveRow);

    // 自分の感情をコントロールしているか
    $nextColLetter_plus8 = Coordinate::stringFromColumnIndex($lastColIndex + 8);
    $nextColLetter_plus9 = Coordinate::stringFromColumnIndex($lastColIndex + 9);
    $nextColLetter_plus10 = Coordinate::stringFromColumnIndex($lastColIndex + 10);
    $range = $nextColLetter_plus8.$fourRow.':'.$nextColLetter_plus10.$fourRow;
    $sheet->setCellValue($nextColLetter_plus8.$fourRow, '自分の感情をコントロールしているか');
    // マージ範囲にそのまま適用
    $sheet->duplicateStyle($sheet1->getStyle('S4:T4'),$range);
    $sheet->mergeCells($range);

    // コントロール＆アチーブメント力自己をコントロールし、目標を達成できるか
    $sheet->setCellValue($nextColLetter_plus8.$fiveRow, 'コントロール＆アチーブメント力自己をコントロールし、目標を達成できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus8.$fiveRow);
    if($code->weight4) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus8.$fiveRow);
    // ビジョン創出力達成するべき目標を設定することができるか
    $sheet->setCellValue($nextColLetter_plus9.$fiveRow, 'ビジョン創出力達成するべき目標を設定することができるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus9.$fiveRow);
    if($code->weight5) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus9.$fiveRow);
    // ポジティブ思考力周囲の状況を柔軟に捉え、適応できるか
    $sheet->setCellValue($nextColLetter_plus10.$fiveRow, 'ポジティブ思考力周囲の状況を柔軟に捉え、適応できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus10.$fiveRow);
    if($code->weight6) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus10.$fiveRow);

    // 相手の状況を適切に認識できているか
    $nextColLetter_plus11 = Coordinate::stringFromColumnIndex($lastColIndex + 11);
    $nextColLetter_plus12 = Coordinate::stringFromColumnIndex($lastColIndex + 12);
    $nextColLetter_plus13 = Coordinate::stringFromColumnIndex($lastColIndex + 13);
    $range = $nextColLetter_plus11.$fourRow.':'.$nextColLetter_plus13.$fourRow;
    $sheet->setCellValue($nextColLetter_plus11.$fourRow, '相手の状況を適切に認識できているか');
    // マージ範囲にそのまま適用
    $sheet->duplicateStyle($sheet1->getStyle('V4:X4'),$range);
    $sheet->mergeCells($range);
    // 対人共感力相手に共感できるか
    $sheet->setCellValue($nextColLetter_plus11.$fiveRow, '対人共感力相手に共感できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus11.$fiveRow);
    if($code->weight7) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus11.$fiveRow);
    // 状況察知力職場の雰囲気を読み取ることができるか
    $sheet->setCellValue($nextColLetter_plus12.$fiveRow, '状況察知力職場の雰囲気を読み取ることができるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus12.$fiveRow);
    if($code->weight8) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus12.$fiveRow);
    // ホスピタリティ発揮力相手のして欲しいことを積極的にできるか
    $sheet->setCellValue($nextColLetter_plus13.$fiveRow, 'ホスピタリティ発揮力相手のして欲しいことを積極的にできるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus13.$fiveRow);
    if($code->weight9) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus13.$fiveRow);

    // 相手に適切に働きかけているか
    $nextColLetter_plus14 = Coordinate::stringFromColumnIndex($lastColIndex + 14);
    $nextColLetter_plus15 = Coordinate::stringFromColumnIndex($lastColIndex + 15);
    $nextColLetter_plus16 = Coordinate::stringFromColumnIndex($lastColIndex + 16);
    $range = $nextColLetter_plus14.$fourRow.':'.$nextColLetter_plus16.$fourRow;
    $sheet->setCellValue($nextColLetter_plus14.$fourRow, '相手に適切に働きかけているか');
    // マージ範囲にそのまま適用
    $sheet->duplicateStyle($sheet1->getStyle('Y4:AA4'),$range);
    $sheet->mergeCells($range);

    // リーダーシップ発揮力集団を目標達成するために積極的に行動できるか
    $sheet->setCellValue($nextColLetter_plus14.$fiveRow, 'リーダーシップ発揮力集団を目標達成するために積極的に行動できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus14.$fiveRow);
    if($code->weight10) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus14.$fiveRow);
    // アサーション発揮力相手のことを考慮しながら自分の考えを主張できるか
    $sheet->setCellValue($nextColLetter_plus15.$fiveRow, 'アサーション発揮力相手のことを考慮しながら自分の考えを主張できるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus15.$fiveRow);
    if($code->weight11) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus15.$fiveRow);
    // 集団適応力人に興味があり、仲間との良好な関係を保つことができるか
    $sheet->setCellValue($nextColLetter_plus16.$fiveRow, '集団適応力人に興味があり、仲間との良好な関係を保つことができるか');
    $sheet->duplicateStyle($sheet1->getStyle('P5'),$nextColLetter_plus16.$fiveRow);
    if($code->weight12) $sheet->duplicateStyle($sheet1->getStyle('Q5'),$nextColLetter_plus16.$fiveRow);
  }
  public function createTitlePlus($sheet,$sheet1,$lastColIndex){
    $plus = 1;
    $startIndex = $lastColIndex + config('const.spreadsheet.maxCol.PFS.0');
    $nextColLetter = Coordinate::stringFromColumnIndex($startIndex+$plus);
    $end = config('const.spreadsheet.maxCol.PFS.1');
    $endColLetter = Coordinate::stringFromColumnIndex($startIndex+$end);

    $sheet->setCellValue($nextColLetter.$this->threeRow, 'PFS結果');
    $sheet->mergeCells($nextColLetter.$this->threeRow .':'.$endColLetter.$this->threeRow);
    $sheet->duplicateStyle( $sheet1->getStyle('AB3:AH3'),$nextColLetter.$this->threeRow .':'.$endColLetter.$this->threeRow);

    $plus++;
    $sheet->setCellValue($nextColLetter.$this->fourRow, '総合');
    $sheet->mergeCells($nextColLetter.$this->fourRow .':'.$nextColLetter.$this->fiveRow);
    $sheet->duplicateStyle( $sheet1->getStyle('N4:N5'), $nextColLetter.$this->fourRow .':'.$nextColLetter.$this->fiveRow);

    $nextColLetter_plus1 = Coordinate::stringFromColumnIndex($startIndex+$plus);
    $plus++;
    $nextColLetter_plus2 = Coordinate::stringFromColumnIndex($startIndex+$plus);
    $plus++;
    $nextColLetter_plus3 = Coordinate::stringFromColumnIndex($startIndex+$plus);
    $plus++;
    $nextColLetter_plus4 = Coordinate::stringFromColumnIndex($startIndex+$plus);
    $plus++;
    $nextColLetter_plus5 = Coordinate::stringFromColumnIndex($startIndex+$plus);
    $plus++;
    $nextColLetter_plus6 = Coordinate::stringFromColumnIndex($startIndex+$plus);

    $sheet->setCellValue($nextColLetter_plus1.$this->fourRow, 'モニタリング領域');
    $sheet->mergeCells($nextColLetter_plus1.$this->fourRow .':'.$nextColLetter_plus3.$this->fourRow);
    $sheet->duplicateStyle( $sheet1->getStyle('AC4:AE4'),$nextColLetter_plus1.$this->fourRow .':'.$nextColLetter_plus3.$this->fourRow);

    $sheet->setCellValue($nextColLetter_plus4.$this->fourRow, 'セルフマネジメント領域');
    $sheet->mergeCells($nextColLetter_plus4.$this->fourRow .':'.$nextColLetter_plus6.$this->fourRow);
    $sheet->duplicateStyle( $sheet1->getStyle('AC4:AE4'),$nextColLetter_plus4.$this->fourRow .':'.$nextColLetter_plus6.$this->fourRow);

    $sheet->setCellValue($nextColLetter_plus1.$this->fiveRow, '対人共感リスク');
    $sheet->duplicateStyle($sheet1->getStyle('AC5'),$nextColLetter_plus1.$this->fiveRow);
    $sheet->setCellValue($nextColLetter_plus2.$this->fiveRow, '状況察知リスク');
    $sheet->duplicateStyle($sheet1->getStyle('AD5'),$nextColLetter_plus2.$this->fiveRow);
    $sheet->setCellValue($nextColLetter_plus3.$this->fiveRow, '業務分担リスク');
    $sheet->duplicateStyle($sheet1->getStyle('AE5'),$nextColLetter_plus3.$this->fiveRow);
    $sheet->setCellValue($nextColLetter_plus4.$this->fiveRow, '感情コントロールリスク');
    $sheet->duplicateStyle($sheet1->getStyle('AF5'),$nextColLetter_plus4.$this->fiveRow);
    $sheet->setCellValue($nextColLetter_plus5.$this->fiveRow, 'ポジティブ思考リスク');
    $sheet->duplicateStyle($sheet1->getStyle('AG5'),$nextColLetter_plus5.$this->fiveRow);
    $sheet->setCellValue($nextColLetter_plus6.$this->fiveRow, '自己肯定リスク');
    $sheet->duplicateStyle($sheet1->getStyle('AH5'),$nextColLetter_plus6.$this->fiveRow);



    // 幅を10にする
    $startIdx = Coordinate::columnIndexFromString($nextColLetter);
    $endIdx   = Coordinate::columnIndexFromString($endColLetter);
    for ($i = $startIdx; $i <= $endIdx; $i++) {
        $colLetter = Coordinate::stringFromColumnIndex($i);
        $sheet->getColumnDimension($colLetter)->setWidth(10);
    }

  }
  public function createBody(
    $sheet,
    $sheet1,
    $codes,
    $value,
    $lastColIndex,
    $plus,
    $row )
    {
        $this->sheet = $sheet;
        $this->sheet1 = $sheet1;
        // PFS
        // レベルを調べている
        $lv = "";
        $score = "";
        if($codes[ 'PFS' ]->threeflag == 1){
            list($lv, $score) = TestController::getStress2(
                $value->PFS->dev1,
                $value->PFS->dev2,
                $value->PFS->dev6,
                );
        }else{
            list($lv, $score) = TestController::getStress(
                $value->PFS->dev1,
                $value->PFS->dev2
                );
        }
        // レベル
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $lv??'');
        $this->setLevelColor($nextColLetter.$row, $lv);
        $plus++;
        // スコア
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $score??'');
        $sheet->duplicateStyle(clone $sheet1->getStyle('M7'), $nextColLetter.$row);
        $this->setScoreColor($nextColLetter.$row, $score);
        $plus++;

        // 適合レベル
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $value->PFS->level??'');
        $this->setLevelColor($nextColLetter.$row, $value->PFS->level);
        // 適合スコア
        $plus++;
        $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
        $sheet->setCellValue($nextColLetter.$row, $value->PFS->score??'');
        $this->setScoreColor($nextColLetter.$row, $value->PFS->score);
        for($i=1;$i<=12;$i++){
            $field = 'dev' . $i;
            $plus++;
            $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
            $sheet->setCellValueExplicit(
                $nextColLetter . $row,
                sprintf('%.1f', (float)$value->PFS->$field),
                DataType::TYPE_STRING
            );
           // $sheet->duplicateStyle(clone $sheet1->getStyle('N7'), $nextColLetter.$row);
            $this->setScoreColor($nextColLetter.$row, $value->PFS->$field);
        }

        $fields = [
            'sougo',
            'personal',
            'state',
            'job',
            'image',
            'positive',
            'self',
        ];

        foreach ($fields as $field) {
            $plus++;
            $nextColLetter = Coordinate::stringFromColumnIndex($lastColIndex + $plus);
            $cell = $nextColLetter . $row;
            $sheet->setCellValueExplicit(
                $cell,
                sprintf('%.1f', (float)$value->PFS->$field),
                DataType::TYPE_STRING
            );
            if($field === 'sougo'){
                $this->setLevelColorPFS($cell, $value->PFS->$field);
            }else{
                $this->setScoreColorPFS($cell, $value->PFS->$field);
            }
        }
    }
    public function setLevelColor($row, $lv)
    {
        if($lv === 1){
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('M8'),$row);
        } elseif($lv === 2) {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L8'),$row);
        }else{
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L7'),$row);
        }
    }
    public function setLevelColorPFS($row, $lv)
    {
        if($lv > 8){
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('M8'),$row);
        }else{
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L7'),$row);
        }
    }
    public function setScoreColorPFS($row, $score)
    {
        if($score > 60){
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('M8'), $row);
        } elseif($score > 52) {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L8'), $row);
        }else{
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L7'), $row);
        }
    }
    public function setScoreColor($row, $score)
    {
        if($score < 35){
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('M8'), $row);
        } elseif($score < 45) {
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L8'), $row);
        }else{
            $this->sheet->duplicateStyle(clone $this->sheet1->getStyle('L7'), $row);
        }
    }
}
