<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;
    public $admin_id;
    public function checkuser($user_id)
    {
        $loginUser = auth()->user()->currentAccessToken();
        $this->admin_id = $loginUser->tokenable->id;
        // 管理者でログインしたとき
        if ($loginUser->tokenable->type == "admin") {
            // $result = User::select("id")->where("id", $user_id)->where("admin_id", $this->admin_id)->count();
            // if ($result < 1) {
            //     return false;
            // }
            return true;
        }
        // パートナーでログイン
        if ($loginUser->tokenable->type == "partner") {
            $subquery = User::select("admin_id")->where([
                "id" => $this->admin_id
                ,"deleted_at" => null
            ]);
            $result = User::whereIn("admin_id", $subquery)
            ->where("id", $user_id)
            ->get();
            if (count($result) < 1) {
                return false;
            }
            $this->admin_id = $result[0][ 'admin_id' ];
            return true;
        }
        return false;
    }

}
