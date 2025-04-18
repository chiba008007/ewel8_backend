<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use App\Models\Exam;
use App\Models\Test;
use App\Models\User;
use App\Libraries\Age;
use Illuminate\Support\Carbon;

class createSpredsheetController extends Controller
{
    //
    function create(Request $request){
        $temp = [];
        $temp['test_id'] = $request->test_id;
        $temp['customer_id'] = $request->customer_id;
        $data = Exam::getExamSpredData($temp);
        $test = Test::getTestDetail($request->test_id);
        $user = User::getDetail($request->customer_id);
        // テンプレートの読み込み
        $status = config('const.consts.status');
        $passflag = config('const.consts.passflag');
        // パスワード
        $passwd = config('const.consts.PASSWORD');
        $spreadsheet = IOFactory::load(storage_path('app/template.xlsx'));
        $sheet = $spreadsheet->getSheet(0);
        $sheet1 = $spreadsheet->getSheet(1);
        $sheet->setCellValue('C1', $user->name);
        $sheet->setCellValue('I1', $test->testname);
        $row = 6;
        foreach($data as $value){
            $sheet->setCellValue('B'.$row, $value->email);
            $sheet->duplicateStyle(clone $sheet1->getStyle('B6'), 'B'.$row);

            $str = $status[0];
            if($value->ended_at){
                $str = $status[2];
            }elseif($value->started_at){
                $str = $status[1];
            }
            $sheet->setCellValue('C'.$row, $str);
            $sheet->duplicateStyle(clone $sheet1->getStyle('C6'), 'C'.$row);
            $sheet->setCellValue('D'.$row, $value->name);
            $sheet->duplicateStyle(clone $sheet1->getStyle('D6'), 'D'.$row);
            $sheet->setCellValue('E'.$row, $value->kana);
            $sheet->duplicateStyle(clone $sheet1->getStyle('E6'), 'E'.$row);
            $pwd = openssl_decrypt($value->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
            $sheet->setCellValue('F'.$row, $pwd);
            $sheet->duplicateStyle(clone $sheet1->getStyle('F6'), 'F'.$row);
            $startAt = $value->started_at;
            $age = "";
            if($startAt){
                $age = ((new Age))->getAge($startAt,$pwd);
            }
            $sheet->setCellValue('G'.$row, $age);
            $sheet->duplicateStyle(clone $sheet1->getStyle('G6'), 'G'.$row);
            $datetime = new Carbon($startAt);
            $sheet->setCellValue('H'.$row, $datetime->format('Y/m/d'));
            $sheet->duplicateStyle(clone $sheet1->getStyle('H6'), 'H'.$row);
            $sheet->setCellValue('I'.$row, $passflag[$value->passflag]);
            $sheet->duplicateStyle(clone $sheet1->getStyle('I6'), 'I'.$row);
            $sheet->setCellValue('J'.$row, $value->memo1);
            $sheet->duplicateStyle(clone $sheet1->getStyle('J6'), 'J'.$row);
            $sheet->setCellValue('K'.$row, $value->memo2);
            $sheet->duplicateStyle(clone $sheet1->getStyle('K6'), 'K'.$row);
            $row++;
        }

        // 保存用ファイル名を生成
        $fileName = 'excel_' . uniqid() . '.xlsx';
        // storage/app/excels/ に保存（事前にこのディレクトリが必要）
        $savePath = "excels/{$fileName}";
        $fullPath = storage_path("app/{$savePath}");

        // 保存ディレクトリがなければ作成
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        $spreadsheet->removeSheetByIndex(1);
        // ファイルとして保存
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // 保存先のファイルURLなどを返す（publicにリンクを貼るならstorage:linkも必要）
        return response()->json([
            'message' => 'Excel saved successfully',
            'file_path' => $savePath,
            'url' => url("storage/{$savePath}") // storage:linkを使っている場合
        ]);
    }
}
