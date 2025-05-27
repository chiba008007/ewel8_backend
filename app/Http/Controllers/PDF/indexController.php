<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\pdfs;

class indexController extends Controller
{
    public $linebreak;

    public function index(Request $request, $id, $code, $birth)
    {

        $obj = new pdfs();
        $pdf = $obj->addPageToPdf($id, $code, $birth);

        $filename = $code . "_" . date('Y') . date('m') . date('d') . ".pdf";
        return $pdf->Output($filename, 'D');

    }

}
