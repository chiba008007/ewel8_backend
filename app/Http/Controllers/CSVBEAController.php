<?php

namespace App\Http\Controllers;

use App\Models\exampfs;
use App\Models\Test;
use App\Models\testparts;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class CSVBEAController extends TestController
{
    //
    public function getBEA(Request $request)
    {
        $code = "BEA";
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        try {

            $test = Test::find($test_id)
            ->select("tests.testname", "a.name as customername", "b.name as partnername")
            ->join('users as a', 'tests.user_id', '=', 'a.id')
            ->join('users as b', 'a.partner_id', '=', 'b.id')
            ->first();

            $exam = DB::select("
                SELECT * FROM exams WHERE test_id = ?
            ", [$test_id]);

            $bea = DB::select(
                "
                SELECT
                    *,
                    date_format(starttime, '%Y/%m/%d') as startdate,
                    date_format(starttime, '%H:%i:%s') as starttimes,
                    SEC_TO_TIME(endtime-starttime) as timer
                FROM exam_bea
                    WHERE id IN (
                        SELECT
                            max(id) as id
                        FROM
                            exam_bea
                        WHERE
                            testparts_id = (SELECT id FROM testparts WHERE test_id = ? AND code=?)
                        GROUP BY exam_id
                )
                ",
                [$test_id,$code]
            );

            $passwd = config('const.consts.PASSWORD');


            $set = [];
            foreach ($bea as $value) {
                $set[$value->exam_id] = $value;
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
                $result[$key]['bea'] = isset($set[$value->id]) ? $set[$value->id] : "";
            }

            $return['list'] = $result;
            $return['test'] = $test;
            return response($return, 200);

        } catch (Exception $e) {
            return response([], 400);
        }


    }
}
