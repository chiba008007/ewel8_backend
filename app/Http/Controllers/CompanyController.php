<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Services\CompanyExportService;

class CompanyController extends Controller
{

    private $companyExportService;
    public function __construct(CompanyExportService $companyExportService)
    {
        $this->companyExportService = $companyExportService;

    }
    //
    public function download(){
        return $this->companyExportService->downloadZip();
    }

    public function downloadFile($file){
        $path = storage_path('app/tmp/' . $file);

        if (!file_exists($path)) {
            abort(404);
        }
         return response()->download($path)->deleteFileAfterSend(true);
    }
}
