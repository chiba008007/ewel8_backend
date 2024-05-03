<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pref;
class PrefContrller extends Controller
{
    //
    public function index()
    {
        //
        $pref = Pref::all();
        return response()->json([
            'status' => true,
            'pref' => $pref
        ]);
    }
}
