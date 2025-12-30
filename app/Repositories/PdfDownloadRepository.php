<?php
namespace App\Repositories;

use Illuminate\Support\Facades\Log;
use App\Models\pdfDownloads;
use App\Models\Exam;
use App\Models\fileuploads;

class PdfDownloadRepository
{

  public function getTrigger()
  {
      // pdfダウンロードするトリガーの取得
      $trigger = pdfDownloads::where([
          'status' => 1,
          'type' => 1
          ])->first();
      if (is_null($trigger)) {
          Log::info("実行データが無いため終了");
          return ;
      }
      return $trigger;
  }

  public function markProcessing()
  {
    // 実行中のデータがあれば処理中止
    $count = pdfDownloads::where([
        'status' => 1,
        'type' => 2
        ])->count();
    if ($count) {
        Log::info("データ実行中のため終了");
        return "データ実行中のため終了";
    }

  }

  public function getTargetExams($trigger)
  {

    $exams = Exam::where([
        'test_id' => $trigger->test_id,
        'customer_id' => $trigger->customer_id,
        'partner_id' => $trigger->partner_id
    ])
    ->whereNotNull(['ended_at'])
    ->whereNull(['deleted_at'])
    ->orderby("id")
    ->get()
    ->values();

    // number を付ける
    $exams->each(function ($item, $index) {
        $item->number = $index + 1;
    });

    return $exams;
  }

  // ファイルを保存したことを添付用のテーブルに保持して、
  // それぞれの利用者がダウンロードできるようにする
  public function setFileupload($index, $type = "zip", $trigger,$zipFilename)
  {
    $prefix = config('const.consts.PDF_PREFIX');
    $filename = $prefix."_".$trigger->test_id."_".date('YmdH');
    $size = filesize(storage_path('/app/public/uploads/').$zipFilename[$index]);
    $params = [];
    $params[ 'test_id'    ] = $trigger->test_id;
    $params[ 'customer_id'] = $trigger->customer_id;
    $params[ 'partner_id' ] = $trigger->partner_id;
    $params[ 'admin_id' ] = 1;
    if ($type == "zip") {
        $params[ 'filename' ] = $filename."_0".($index + 1)."_inv.zip";
    } else {
        $params[ 'filename' ] = $filename."_0".($index).".pdf";
    }
    $params[ 'filepath' ] = "uploads/".$zipFilename[$index];
    $params[ 'size' ] = $size;
    $params[ 'status' ] = 1;
    $params[ 'created_at' ] = date("Y-m-d H:i:s");
    $params[ 'updated_at' ] = date("Y-m-d H:i:s");
    fileuploads::insert($params);

  }

  // 取得データを実行済に変更
  public function markCompleted($trigger){
    pdfDownloads::where('id', $trigger->id)->update([
      'type' => 3,
    ]);
  }

}
