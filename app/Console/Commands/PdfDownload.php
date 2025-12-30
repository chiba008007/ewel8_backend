<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZipService;
use App\Services\PdfBatchDownloadService;

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
    private $pdfBatchDownloadService;

    // private $prefix = "batchdownload_";
    public function __construct(
        ZipService $zipService,
        PdfBatchDownloadService $pdfBatchDownloadService
    )
    {
        parent::__construct();
        $this->zipService = $zipService;
        $this->pdfBatchDownloadService = $pdfBatchDownloadService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('PDF Download start');
        $message = $this->pdfBatchDownloadService->execute();
        if($message){
            $this->info($message);
        }
        $this->info('PDF Download end');

    }
}
