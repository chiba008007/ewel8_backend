<?php

namespace App\Http\Controllers;

use App\Models\exampfs;
use App\Models\Test;
use App\Models\testparts;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class CsvsController extends TestController
{
    //
    public function getPfs(Request $request)
    {
        $code = "PFS";
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        try {
            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }

            $test = Test::find($test_id)
            ->select("tests.testname", "a.name as customername", "b.name as partnername")
            ->join('users as a', 'tests.user_id', '=', 'a.id')
            ->join('users as b', 'a.partner_id', '=', 'b.id')
            ->first();

            $exam = DB::select("
                SELECT * FROM exams WHERE test_id = ?
            ", [$test_id]);

            $pfs = DB::select(
                "
                SELECT
                    *,
                    date_format(starttime, '%Y/%m/%d') as startdate,
                    date_format(starttime, '%H:%i:%s') as starttimes,
                    SEC_TO_TIME(endtime-starttime) as timer
                FROM exampfses
                    WHERE id IN (
                        SELECT
                            max(id) as id
                        FROM
                            exampfses
                        WHERE
                            testparts_id = (SELECT id FROM testparts WHERE test_id = ? AND code=?)
                        GROUP BY exam_id
                )
                ",
                [$test_id,$code]
            );

            $passwd = config('const.consts.PASSWORD');
            $set = [];
            foreach ($pfs as $value) {
                $maxes = "";
                if ($value->endtime) {
                    $max = [];
                    $max['dev1'] = $value->dev1;
                    $max['dev2'] = $value->dev2;
                    $max['dev3'] = $value->dev3;
                    $max['dev4'] = $value->dev4;
                    $max['dev5'] = $value->dev5;
                    $max['dev6'] = $value->dev6;
                    $max['dev7'] = $value->dev7;
                    $max['dev8'] = $value->dev8;
                    $max['dev9'] = $value->dev9;
                    $max['dev10'] = $value->dev10;
                    $max['dev11'] = $value->dev11;
                    $max['dev12'] = $value->dev12;
                    $maxes = array_keys($max, max($max));
                }
                $set[$value->exam_id] = $value;
                $set[$value->exam_id]->max = ($maxes) ? $maxes[0] : "";
            }
            $result = [];
            foreach ($exam as $key => $value) {
                $pwd = openssl_decrypt($value->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                $result[$key][ 'pwd' ] = ($pwd == "password") ? "" : $pwd;
                if ($result[$key][ 'pwd' ]) {
                    $today = $value->created_at;
                    $age = floor((strtotime($today) - strtotime($pwd)) / (60 * 60 * 24 * 365));
                } else {
                    $age = '';
                }
                $result[$key][ 'age' ] = $age;
                $result[$key][ 'exam' ] = $value;
                $result[$key]['pfs'] = isset($set[$value->id]) ? $set[$value->id] : "";
            }
            $return['list'] = $result;
            $return['test'] = $test;
            return response($return, 200);

        } catch (Exception $e) {
            return response([], 400);
        }


    }
}
