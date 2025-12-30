<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Repositories\PdfDownloadRepository;
use App\Services\PdfGenerateService;
use App\Services\ZipGenerateService;
use App\Services\MailService;
use App\Models\User;
use App\Models\Test;

class PdfBatchDownloadService
{
  private $repository;
  private $pdfService;
  private $zipService;
  private $test_id;
  private $partner_id;
  private $customer_id ;
  private $zipDir;
  private $mailService;

  public function __construct(
    PdfDownloadRepository $repository,
    PdfGenerateService $pdfService,
    ZipGenerateService $zipService,
    MailService $mailService,
    )
  {
    $this->repository = $repository;
    $this->pdfService = $pdfService;
    $this->zipService = $zipService;
    $this->mailService = $mailService;
  }

  public function execute()
  {
    Log::info('PDF Download start (service)');
    // pdfダウンロードするトリガーの取得
    $trigger = $this->repository->getTrigger();
    if (!$trigger) {
        Log::info('実行データが無いため終了');
        return '実行データが無いため終了';
    }
    Log::info('pdf trigger', $trigger->toArray());
    $code = $trigger->code;

    // 実行中のデータがあれば処理中止
    $message = $this->repository->markProcessing();
    if ($message) {
        Log::info($message);
        return $message;
    }
    $this->test_id = $trigger->test_id;
    $this->partner_id = $trigger->partner_id;
    $this->customer_id = $trigger->customer_id;

    $user = User::find($trigger->customer_id);
    $test = Test::find($trigger->test_id);
    Log::info('PDF一括ダウンロードファイル生成開始のお知らせ:'.$user->tanto_name);
    Log::info('メールアドレス:'.$user->tanto_address);
    Log::info('テスト名:'.$test->testname);

    // PDForZipを生成する個所を準備
    $dir = $this->pdfService->preparePdfDirectory($this->test_id);

    $exams = $this->repository->getTargetExams($trigger);

    // アップロード用のファイルパスを保持
    $this->zipDir = $this->pdfService->prepareUploadDirectory();
    $filename = "";
    if ($trigger->code === 1) {
      // 1ファイルにまとめる
      $filename = $this->pdfService->generateGroupedPdf($exams, $trigger, $this->zipDir);

    } else { // それぞれPDFを作る
      $filename = $this->zipService->generateZip($exams, $trigger, $this->zipDir);
    }
    // 取得データを実行済に変更
    $this->repository->markCompleted($trigger);
    $this->mailService->sendCompleteMail($user, $test, $filename);
    return ;
  }
}
