<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Test extends Model
{
    use HasFactory;

    public static function getTestDetail($id)
    {
        $result = DB::table("tests")->find($id);
        return $result;
    }
    public function getTestParts($testid)
    {
        $result = DB::table("tests")
            ->select(["testpdfs.*"
            ,"users.name"
            ,"partner.element1"
            ,"partner.element2"
            ,"partner.element3"
            ,"partner.element4"
            ,"partner.element5"
            ,"partner.element6"
            ,"partner.element7"
            ,"partner.element8"
            ,"partner.element9"
            ,"partner.element10"
            ,"partner.element11"
            ,"partner.element12"
            ])
            ->join('testpdfs', 'tests.id', '=', 'testpdfs.test_id')
            ->join('users', 'tests.customer_id', '=', 'users.id')
            ->join('users as partner', 'tests.partner_id', '=', 'partner.id')
            ->where([
                ["tests.id","=",$testid],
                ["testpdfs.status","=",1],
            ])
            ->get();
        return $result;
    }
}
