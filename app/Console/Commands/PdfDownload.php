<?php

namespace App\Console\Commands;

use App\Models\Exam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\pdfs;
use App\Services\ZipService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\fileuploads;
use App\Models\pdfDownloads;
use App\Models\User;
use ZipArchive;
use App\Mail\PdfDownloadMail;
use App\Models\Test;
use Illuminate\Support\Facades\Mail;

class PdfDownload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pdf-download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $zipService;
    private $zipFilename;
    private $pdfSlice = 100;
    private $pdfSlice2 = 50;

    private $test_id;
    private $partner_id;
    private $customer_id ;
    private $zipDir;
    public function __construct(ZipService $zipService)
    {
        parent::__construct();
        $this->zipService = $zipService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // PFS用のチャートグラフを生成するよう
        require_once(public_path()."/PDF/pfsCreateGraph.php");
        //
        //標準出力&ログに出力するメッセージのフォーマット
        $message = '[' . date('Y-m-d h:i:s') . ']PDF Download start';

        //INFOレベルでメッセージを出力
        $this->info($message);
        Log::info($message);
        //ログを書き出す処理はこちら
        //Log::setDefaultDriver('batch');
        Log::info($message);
        // pdfダウンロードするトリガーの取得
        $trigger = pdfDownloads::where([
            'status' => 1,
            'type' => 1
            ])->first();
        if (is_null($trigger)) {
            $this->info("実行データが無いため終了");
            Log::info("実行データが無いため終了");

            exit();
        }
        $code = $trigger->code;

        // 実行中のデータがあれば処理中止
        $count = pdfDownloads::where([
            'status' => 1,
            'type' => 2
            ])->count();
        if ($count) {
            $this->info("データが実行中のため終了");
            Log::info("データ実行中のため終了");
        }

        //取得データを実行中に変更
        // pdfDownloads::where('id', $trigger->id)->update([
        //     'type' => 2,
        // ]);

        $this->test_id = $trigger->test_id;
        $this->partner_id = $trigger->partner_id;
        $this->customer_id = $trigger->customer_id;

        $user = User::find($trigger->customer_id);
        $test = Test::find($trigger->test_id);
        $this->info('PDF一括ダウンロードファイル生成開始のお知らせ:'.$user->tanto_name);
        $this->info('メールアドレス:'.$user->tanto_address);
        $this->info('テスト名:'.$test->testname);
        Log::info('PDF一括ダウンロードファイル生成開始のお知らせ:'.$user->tanto_name);
        Log::info('メールアドレス:'.$user->tanto_address);
        Log::info('テスト名:'.$test->testname);

        $path = storage_path('app/public/PDF/');
        $dir = $path.$this->test_id."/";
        Storage::makeDirectory("/public/PDF/".$this->test_id."/");


        $exams = Exam::where([
            'test_id' => $this->test_id,
            'customer_id' => $this->customer_id,
            'partner_id' => $this->partner_id
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
        $passwd = config('const.consts.PASSWORD');
        // アップロード用のファイルパスを保持
        $this->zipDir = storage_path('/app/public/uploads');
        if (!file_exists($this->zipDir)) {
            mkdir($this->zipDir, 0777, true);
        }

        $uploadFileMail = "";
        // 1ファイル50人ずつにまとめて作る
        if ($code == 1) {
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
                    if ($examGroupCount - 1 != $no) {
                        $pdf->AddPage();
                    }
                    $no++;
                }

                $start = $examGroup->first()->number;
                //$end   = $examGroup->last()->number;
                $filename = "PDF_".Str::random(40);
                $savefile = "{$this->zipDir}/{$filename}.pdf";
                $this->zipFilename[$start] = $filename.".pdf";
                $pdf->Output($savefile, 'F');
                $this->setFileupload($start, "pdf");
                $uploadFileMail .= "PDF_".$start.".pdf / ";
            }
        }
        // ZIP化
        if ($code == 2) {
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
                $this->info($message);

            }

            $chunks = array_chunk($pdfPaths, $this->pdfSlice);

            if ($this->downloadZip($chunks)) {
                // 成功
                foreach ($chunks as $index => $pdfGroup) {
                    $this->setFileupload($index);
                    $uploadFileMail .= "zip_".($index + 1).".zip / ";
                }
            } else {
                // 失敗

            }
        }



        //取得データを実行済に変更
        pdfDownloads::where('id', $trigger->id)->update([
            'type' => 3,
        ]);
        // メール配信
        Log::info('PDF一括ダウンロードファイル生成完了のお知らせ:'.$user->tanto_name);
        $this->info('メールアドレス:'.$user->tanto_address);
        $mailbody = [];
        $mailbody[ 'title' ] = "PDF一括ダウンロードファイル生成完了のお知らせ";
        $mailbody[ 'name' ] = $user->name;
        $mailbody[ 'person' ] = $user->tanto_name;
        $mailbody[ 'testname' ] = $test->testname;
        $mailbody[ 'uploadFileMail' ] = $uploadFileMail;
        Mail::to($user->tanto_address)->send(new PdfDownloadMail($mailbody));

        //標準出力&ログに出力するメッセージのフォーマット
        $message = '[' . date('Y-m-d h:i:s') . ']PDF Download end';
        //INFOレベルでメッセージを出力
        $this->info($message);
    }
    public function setFileupload($index, $type = "zip")
    {
        $size = filesize(storage_path('/app/public/uploads/').$this->zipFilename[$index]);
        $params = [];
        $params[ 'test_id'    ] = $this->test_id;
        $params[ 'customer_id'] = $this->customer_id;
        $params[ 'partner_id' ] = $this->partner_id;
        $params[ 'admin_id' ] = 1;
        if ($type == "zip") {
            $params[ 'filename' ] = "zip_".($index + 1).".zip";
        } else {
            $params[ 'filename' ] = "pdf_".($index).".pdf";
        }
        $params[ 'filepath' ] = "uploads/".$this->zipFilename[$index];
        $params[ 'size' ] = $size;
        $params[ 'status' ] = 1;
        $params[ 'created_at' ] = date("Y-m-d H:i:s");
        $params[ 'updated_at' ] = date("Y-m-d H:i:s");
        fileuploads::insert($params);
    }
    public function downloadZip($chunks)
    {
        foreach ($chunks as $index => $pdfGroup) {
            $this->zipFilename[$index] = Str::random(40).".zip";
            $zipPath = $this->zipDir . "/".$this->zipFilename[$index];
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->error("ZIP作成に失敗: $zipPath");
                Log::info("ZIP作成に失敗しました:".$zipPath);
                continue;
            }

            foreach ($pdfGroup as $file) {
                $zip->addFile($file, basename($file));
            }

            $zip->close();
            $this->info("ZIP作成完了: $zipPath");
            Log::info("ZIP作成完了:".$zipPath);

            // PDFを削除
            foreach ($pdfGroup as $filePath) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                    $this->info("$filePath を削除しました");
                }
            }
        }
        return true;
    }


}
