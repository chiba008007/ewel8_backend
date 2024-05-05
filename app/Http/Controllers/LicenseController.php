<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LicenseController extends Controller
{
    //
    public function index()
    {
        //
        $license = config('const.consts.LISENCE');
        return response()->json([
            'status' => true,
            'license' => $license
        ]);
    }

}
