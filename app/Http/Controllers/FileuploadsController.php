<?php

namespace App\Http\Controllers;

use App\Models\fileuploads;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;

class FileuploadsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function list(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        // $admin_id = $loginUser->tokenable->id;

        $partner_id = $request->partner_id;
        $customer_id = $request->customer_id;

        $list = fileuploads::selectRaw('DATE_FORMAT(created_at, "%Y年%m月%d日") AS date')
            ->selectRaw('id,partner_id,admin_id,filename,filepath,size,openflag,status')
            ->where([
            'customer_id' => $customer_id,
            'partner_id' => $partner_id,
            'status' => 1
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response($list, 201);
    }
    public function openFlag(Request $request)
    {
        fileuploads::where([
            'id' => $request->id
        ])->update(['openflag' => 1]);
        return response(true, 201);
    }
    public function deleteStatus(Request $request)
    {
        fileuploads::where([
            'id' => $request->id
        ])->update(['status' => 0]);
        return response(true, 201);
    }
}
