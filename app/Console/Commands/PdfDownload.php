<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\pdfs;

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

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        //標準出力&ログに出力するメッセージのフォーマット
        $message = '[' . date('Y-m-d h:i:s') . ']PDF Download start';

        //INFOレベルでメッセージを出力
        $this->info($message);
        //ログを書き出す処理はこちら
        //Log::setDefaultDriver('batch');
        Log::info($message);

        //http://localhost:8000/pdf/72/code/jhz/birth/1999-01-01
        $id = 72;
        $code = "jhz";
        $birth = '1999-01-01';
        $path = storage_path('app/public/PDF');
        /*
        $obj = new pdfs();
        for ($i = 0;$i < 10;$i++) {
            $pdf = $obj->addPageToPdf($id, $code, $birth);
            $pdf->AddPage();
        }

        $filename = $path."/1-100".".pdf";
        $pdf->Output($filename, 'F');
        */
        for ($i = 0;$i < 10;$i++) {
            $obj = new pdfs();
            $pdf = $obj->addPageToPdf($id, $code, $birth);
            $pdf->AddPage();
            $filename = $path."/".$i.".pdf";
            $pdf->Output($filename, 'F');
        }


    }
}
