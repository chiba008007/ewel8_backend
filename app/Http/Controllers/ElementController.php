<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Element;

class ElementController extends Controller
{
    //
    public function index()
    {
        //
        $element = Element::all();
        return response()->json([
            'status' => true,
            'element' => $element
        ]);
    }
}
