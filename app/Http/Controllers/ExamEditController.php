<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ExamEditController extends Controller
{
    public function getExamEditData(Request $request)
    {
        $passwd = config('const.consts.PASSWORD');
        $loginUser = auth()->user()->currentAccessToken();
        $partner_id = $request->partner_id;
        $customer_id = $request->customer_id;
        $test_id = $request->test_id;
        $id = $request->edit_id;
        $exams = Exam::Where(
            [
                'id' => $id,
                'partner_id' => $partner_id,
                'customer_id' => $customer_id,
                'test_id' => $test_id,
            ]
        )->first();

        $emails = Exam::select("email")
        ->where(
            [
                'partner_id' => $partner_id,
                'customer_id' => $customer_id,
                'test_id' => $test_id,
            ]
        )
        ->where('id', '!=', $id)
        ->pluck('email')->toArray();

        $pwd = openssl_decrypt($exams['password'], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
        $exams['birth'] = $pwd;
        $exams['emailList'] = $emails;
        return response($exams, 200);
    }

    public function editExamEditData(Request $request)
    {
        $passwd = config('const.consts.PASSWORD');
        $loginUser = auth()->user()->currentAccessToken();
        $partner_id = $request->partner_id;
        $id = $request->edit_id;
        $exam = Exam::where('id', $id)->where('partner_id', $partner_id)->first();

        $pwd = openssl_encrypt($request->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
        try {
            if ($exam) {
                $exam->email = $request->email;
                $exam->name = preg_replace("/ /", "　", $request->name);
                $exam->kana = preg_replace("/ /", "　", $request->kana);
                $exam->gender = $request->gender;
                $exam->password = $pwd;
                $exam->passflag = $request->passflag;
                $exam->memo1 = $request->memo1;
                $exam->memo2 = $request->memo2;
                $exam->save();
            }
            return response("success", 200);
        } catch (Exception $e) {
            return response("error", 400);
        }

    }
}
