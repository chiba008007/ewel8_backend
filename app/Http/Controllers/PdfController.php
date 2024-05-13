<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PdfController extends Controller
{
    //
    public function index()
    {
        //
        $pdf = config('const.consts.PDF');
        return response()->json([
            'status' => true,
            'pdf' => $pdf
        ]);
    }

}
