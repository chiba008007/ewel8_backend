<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Repositories\PdfDownloadRepository;
use ZipArchive;

class ZipGenerateService
{
  // 1ファイルにまとめる件数
  private $pdfSlice = 100;

    private $repository;

  public function __construct(
    PdfDownloadRepository $repository,
  )
  {
    $this->repository = $repository;
  }

  public function generateZip($exams,$trigger,$zipDir)
  {
    $path = storage_path('app/public/PDF/');
    $dir = $path.$trigger->test_id;
    $passwd = config('const.consts.PASSWORD');
    $prefix = config('const.consts.PDF_PREFIX');
    // それぞれPDFを作る
    $pdfPaths = [];
    foreach ($exams as $value) {
      $birth = openssl_decrypt($value->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
      $obj = new pdfs();
      $pdf = $obj->addPageToPdf($value->id, $value->email, $birth);

      $filename = $dir."/".$value->email.".pdf";
      $pdf->Output($filename, 'F');
      $pdfPaths[] = $filename;
      //標準出力&ログに出力するメッセージのフォーマット
      $message = '[' . date('Y-m-d h:i:s') . ']'.$filename."ファイルを作成それぞれPDFを作っていく";
      //INFOレベルでメッセージを出力
      Log::info($message);
    }
    $uploadFileMail = "";
    $chunks = array_chunk($pdfPaths, $this->pdfSlice);
    if ($zipFilename = $this->downloadZip($chunks, $trigger, $zipDir)) {
        // 成功
        foreach ($chunks as $index => $pdfGroup) {
            $this->repository->setFileupload($index,"zip",$trigger,$zipFilename);
            $uploadFileMail .= $prefix."_".$trigger->test_id."_".date('Ymd')."_0".($index + 1)."_inv.zip / ";
        }
    } else {
        // 失敗

    }
    $uploadFileMail = substr($uploadFileMail, 0, -3);
    return $uploadFileMail;

  }

  public function downloadZip($chunks, $trigger, $zipDir)
  {
    $prefix = config('const.consts.PDF_PREFIX');
    $zipFilename = [];
    $filename = $prefix."_".$trigger->test_id."_".date('YmdH');
    foreach ($chunks as $index => $pdfGroup) {
        $zipFilename[$index] = $filename."_0".($index + 1)."_inv.zip";
        $zipPath = $zipDir . "/".$zipFilename[$index];
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::info("ZIP作成に失敗しました:".$zipPath);
            continue;
        }

        foreach ($pdfGroup as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();
        Log::info("ZIP作成完了:".$zipPath);

        // PDFを削除
        foreach ($pdfGroup as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    return $zipFilename;
  }

}
