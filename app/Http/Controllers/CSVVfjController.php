<?php

namespace App\Http\Controllers;

use App\Models\exampfs;
use App\Models\Test;
use App\Models\testparts;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class CSVVfjController extends TestController
{
    //
    public function getVFJ(Request $request)
    {
        $code = "VFJ";
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        try {

            $user = User::find($user_id);
            $partner = $user->partner;
            $elements = [];
            for ($i = 1;$i <= 14;$i++) {
                $elements[$i] = $partner['element'.$i];
            }

            $test = Test::find($test_id)
            ->select("tests.testname", "a.name as customername", "b.name as partnername")
            ->join('users as a', 'tests.user_id', '=', 'a.id')
            ->join('users as b', 'a.partner_id', '=', 'b.id')
            ->first();

            $exam = DB::select("
                SELECT * FROM exams WHERE test_id = ?
            ", [$test_id]);

            $vfj = DB::select(
                "
                SELECT
                    *,
                    date_format(starttime, '%Y/%m/%d') as startdate,
                    date_format(starttime, '%H:%i:%s') as starttimes,
                    SEC_TO_TIME(endtime-starttime) as timer
                FROM examvfj
                    WHERE id IN (
                        SELECT
                            max(id) as id
                        FROM
                            examvfj
                        WHERE
                            testparts_id = (SELECT id FROM testparts WHERE test_id = ? AND code=?)
                        GROUP BY exam_id
                )
                ",
                [$test_id,$code]
            );

            $passwd = config('const.consts.PASSWORD');


            $set = [];
            foreach ($vfj as $value) {
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
                $result[$key]['vfj'] = isset($set[$value->id]) ? $set[$value->id] : "";
            }

            $return['list'] = $result;
            $return['test'] = $test;
            $return['elements'] = $elements;
            return response($return, 200);

        } catch (Exception $e) {
            return response([], 400);
        }


    }
}
