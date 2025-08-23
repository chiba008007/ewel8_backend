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
use App\Models\Test;
use Illuminate\Support\Carbon;

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
        //   $partner_id = $request->partner_id;
        $userdata = User::where('partner_id', $edit_id)
        ->whereNull('deleted_at')
        ->get();

        return response()->json($userdata, 200);
    }


    public function getExec(Request $request)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $admin_id = $loginUser->tokenable->id;

        $customer_id = $request->customer_id;
        $partner_id = $request->partner_id;
        $start = Carbon::parse($request->startdaytime)->startOfDay();
        $end   = Carbon::parse($request->enddaytime)->endOfDay();

        $results = Test::select('tests.id', 'tests.testname')
            ->selectRaw("
                COUNT(CASE WHEN exams.ended_at IS NULL THEN 1 END) as totalCount,
                COUNT(CASE WHEN exams.ended_at IS NOT NULL THEN 1 END) as examCount
            ")
            ->leftJoin('exams', function ($join) use ($start, $end) {
                $join->on('tests.id', '=', 'exams.test_id')
                    ->whereNull('exams.deleted_at')
                    ->whereBetween('exams.created_at', [$start, $end]);
            })
            ->where('tests.partner_id', $partner_id) // partner_id は必須条件
            ->when($customer_id, function ($query, $customer_id) {
                // customer_id が存在する場合のみ条件を付与
                return $query->where('tests.customer_id', $customer_id);
            })
            ->groupBy('tests.id', 'tests.testname')
            ->get();

        return response()->json($results, 200);
    }

}
