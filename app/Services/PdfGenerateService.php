<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\pdfs;
use App\Repositories\PdfDownloadRepository;

class PdfGenerateService
{

  private $repository;

  public function __construct(
    PdfDownloadRepository $repository,
    )
  {
    $this->repository = $repository;
  }

  // 1ファイルにまとめる件数
  private $pdfSlice2 = 50;

  // PDForZipを生成する個所を準備
  public function preparePdfDirectory($test_id)
  {
    $path = storage_path('app/public/PDF/');
    $dir = $path.$test_id."/";
    Storage::makeDirectory("/public/PDF/".$test_id."/");
    return $dir;
  }

  // アップロード用のファイルパスを保持
  public function prepareUploadDirectory(){
    $zipDir = storage_path('/app/public/uploads');
    if (!file_exists($zipDir)) {
        mkdir($zipDir, 0777, true);
    }
    return $zipDir;
  }

  // 1ファイル50人ずつにまとめて作る
  public function generateGroupedPdf($exams, $trigger, $zipDir)
  {
    $prefix = config('const.consts.PDF_PREFIX');
    $passwd = config('const.consts.PASSWORD');
    $chunks = $exams->chunk($this->pdfSlice2);
    foreach ($chunks as $index => $examGroup) {
        $obj = new pdfs();
        $examGroupCount = count($examGroup);
        $no = 0;
        foreach ($examGroup as $exam) {
            $id = $exam->id;
            $code = $exam->email;
            $birth = openssl_decrypt($exam->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
            $pdf = $obj->addPageToPdf($id, $code, $birth);
            // if ($examGroupCount - 1 != $no) {
            //     $pdf->AddPage();
            // }
            $no++;
        }

        $start = $examGroup->first()->number;

        //$end   = $examGroup->last()->number;
        $filename = $prefix."_".$trigger->test_id."_".date('YmdH')."_0".$start;
        $savefile = "{$zipDir}/{$filename}.pdf";
        $zipFilename[$start] = $filename.".pdf";
        $pdf->Output($savefile, 'F');
        $this->repository->setFileupload($start,"pdf",$trigger,$zipFilename);
        return $filename;
    }
  }

}
