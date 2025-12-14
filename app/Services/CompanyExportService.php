<?php

namespace App\Services;

use ZipArchive;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\User;

class CompanyExportService
{

    private ZipArchive $zip;
    private string $zipPath;

    public function downloadZip()
    {
        //データの取得を行う
        $partner = User::getPartnerData();
        $customer = User::getCustomerData();

        // storageファイルの作成
        $this->makeStorageFile();
        $this->openZip();
        // エクセルファイルの追加
        $this->addCompanyExcel($partner,$customer);
        $this->closeZip();
        return basename($this->zipPath);
        // ダウンロード
        //return response()->download($this->zipPath)->deleteFileAfterSend(true);
    }

    // storageファイルの作成
    private function makeStorageFile(){
      $time = time();
      $dir = storage_path('app/tmp');
      // 念のため削除
      foreach (glob($dir . '/*.zip') as $file) {
          if (is_file($file)) {
              unlink($file);
          }
      }
      $this->zipPath = $dir . '/company_'.$time.'.zip';
      if (file_exists($this->zipPath)) {
          unlink($this->zipPath);
      }
    }
    // zipファイルのオープン
    private function openZip(){
     // $this->zipPath = storage_path('app/tmp/company.zip');
      $this->zip = new ZipArchive();
      $this->zip->open(
      $this->zipPath,
        ZipArchive::CREATE | ZipArchive::OVERWRITE
      );
    }
     // エクセルファイルの追加
    private function addCompanyExcel($partner, $customer): void
    {
        $this->zip->addFromString(
            'customer_list.xlsx',
            $this->createCustomerExcelBinary($customer)
        );
        $this->zip->addFromString(
            'partner_list.xlsx',
            $this->createPartnerExcelBinary($partner)
        );
    }

    // Excelの作成
    private function createCustomerExcelBinary($customers)
    {

      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setCellValue('A1', '企業名');
      $sheet->setCellValue('B1', 'パートナー企業名');
      $sheet->setCellValue('C1', '担当者アドレス');
      $sheet->setCellValue('D1', '担当者アドレス2');
      $sheet->setCellValue('E1', '更新日');
      $sheet->setCellValue('F1', '登録日');

      $row = 2;
      foreach ($customers as $customer) {
          $sheet->setCellValue('A' . $row, $customer->name ?? '');
          $sheet->setCellValue('B' . $row, $customer->partner->name ?? '');
          $sheet->setCellValue('C' . $row, $customer->tanto_address ?? '');
          $sheet->setCellValue('D' . $row, $customer->tanto_address2 ?? '');
          $sheet->setCellValue('E' . $row, $customer->updated_at ?? '');
          $sheet->setCellValue('F' . $row, $customer->created_at ?? '');
          $row++;
      }

      $writer = new Xlsx($spreadsheet);

      ob_start();
      $writer->save('php://output');
      return ob_get_clean();
    }

    private function createPartnerExcelBinary($partners)
    {

      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setCellValue('A1', '企業名');
      $sheet->setCellValue('B1', '更新日');
      $sheet->setCellValue('C1', '登録日');
      $sheet->setCellValue('D1', '管理システム名');

      $row = 2;
      foreach ($partners as $partner) {
          $sheet->setCellValue('A' . $row, $partner->name ?? '');
          $sheet->setCellValue('B' . $row, $partner->updated_at ?? '');
          $sheet->setCellValue('C' . $row, $partner->created_at ?? '');
          $sheet->setCellValue('D' . $row, $partner->system_name ?? '');
          $row++;
      }

      $writer = new Xlsx($spreadsheet);

      ob_start();
      $writer->save('php://output');
      return ob_get_clean();
    }



    // ZIPを閉じる
    private function closeZip(): void
    {
      $this->zip->close();
    }
}
