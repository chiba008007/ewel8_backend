<?php

namespace App\Http\Controllers;

use App\Mail\CustomerAddMail;
use App\Mail\CustomerAddMailPassword;
use App\Models\fileuploads;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userlisence;
use App\Models\userpdf;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\SetUserDataMail;
use App\Mail\EditUserDataMail;
use App\Mail\SetUserDataMailPassword;
use App\Mail\EditPartnerEditMail;
use Illuminate\Support\Facades\Log;

class TestExecController extends Controller
{
    public const G_ADMIN = "admin";
    public const G_PARTNER = "partner";
    public function checkAdmin()
    {
        $loginUser = auth()->user()->currentAccessToken();
        if ($loginUser->tokenable->type != self::G_ADMIN) {
            throw new Exception();
        }
    }
    //
    public function getCustomerExec(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $admin_id = $loginUser->tokenable->id;

        $edit_id = $request->edit_id;
        $partner_id = $request->partner_id;
        $userdata = User::where('partner_id', $edit_id)
        ->whereNull('deleted_at')
        ->get();

        return response()->json($userdata, 200);
    }

}
