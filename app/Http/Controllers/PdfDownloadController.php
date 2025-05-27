<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PdfDownloadController extends Controller
{
    //
    public function index()
    {
        // apiから実施
        // pdf一括の実行予定を保存する
        return response(true, 200);
    }
}
