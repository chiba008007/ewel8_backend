<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\RemainCountMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class Exam extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'kana',
        'gender',
        'test_id',
        'type',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ended_at' => 'datetime', // ← 追加
        // 'password' => 'hashed',
    ];

    protected $rules = ['email' => 'required|unique'];

    public static function getbuldInsertKey()
    {

        $buldInsertKey = [
            "test_id",
            "partner_id",
            "customer_id",
            "param",
            "email",
            "password",
            "type",
            "created_at"
        ];

        return $buldInsertKey;
    }
    public static function getExamSpredData($temp)
    {
        $exams = DB::table('exams')
        ->where([
            'test_id' => $temp[ 'test_id' ],
            'customer_id' => $temp[ 'customer_id' ],
        ])
        ->whereNull('deleted_at');
        if ($temp[ 'type' ] === 2) {
            $exams = $exams->wherenotNull('ended_at');
        }
        $exams = $exams->orderBy('id')
        ->get();
        return $exams;
    }


    public static function bulkInsert($data)
    {

        $key = implode(",", self::getbuldInsertKey());
        $sql = "INSERT INTO exams (".$key.") VALUES ";
        $aline = [];
        foreach ($data as $value) {
            $sql .= "(";
            $aimp = [];
            foreach ($value as $val) {
                $aimp[] = "'?'";
                $aline[] = $val;
            }
            $implode = implode(",", $aimp);
            $sql .= $implode."),";
        }
        $sql = preg_replace("/,$/", "", $sql);

        try {
            return DB::insert($sql, $aline);
        } catch (\Exception $e) {
            echo "error\n";
            var_dump($e);
        }

        return true;
    }
    public static function setEndTime()
    {
        $loginUser = auth()->user()->currentAccessToken();
        $todo = Exam::find($loginUser->tokenable->id);
        // 受検を行うテストの総数と受検済みの数が同じときにテスト時間の更新
        $testcount = testparts::where([
            "test_id" => $todo->test_id,
            "status" => 1,
        ])->count();
        $examfin = examfins::where("exam_id", $loginUser->tokenable->id)->count();
        if (!$todo->ended_at && $testcount === $examfin) {
            $todo->ended_at = date("Y-m-d H:i:s");
            $todo->save();
        }
    }
    public static function sendRemainMail($request)
    {
        $params = $request->params;
        $tests = DB::table('tests')
            ->leftJoin('exams', function ($join) {
                $join->on('exams.test_id', '=', 'tests.id')
                    ->whereNull('exams.deleted_at')
                    ->whereNull('exams.ended_at');
            })
            ->select([
            'tests.mailremaincount',
            'tests.partner_id',
            'tests.testname',
            DB::raw('COUNT(exams.id) as count_examId'),
            DB::raw("DATE_FORMAT(startdaytime, '%Y/%m/%d') as startdate"),
            DB::raw("DATE_FORMAT(enddaytime, '%Y/%m/%d') as enddate")
            ])
            ->where([
            "params" => $params,
            "status" => 1,
            ])
            ->groupBy('tests.id', 'tests.mailremaincount')
            ->first();
        $mailremaincount = $tests->mailremaincount;
        $count_examId = $tests->count_examId;
        if ($mailremaincount === $count_examId && $mailremaincount > 0) {
            // 受検残数チェックの時にメール
            $users = DB::table('users')->find($tests->partner_id);

            Log::info('検査数のお知らせ:'.$users->person);
            Log::info('メールアドレス:'.$users->person_address);

            $mailbody = [];
            $mailbody[ 'title' ] = "検査数のお知らせ";
            $mailbody[ 'name' ] = $users->name;
            $mailbody[ 'person' ] = $users->person;
            $mailbody[ 'rest' ] = $mailremaincount;
            $mailbody[ 'testname' ] = $tests->testname;
            $mailbody[ 'startdate' ] = $tests->startdate;
            $mailbody[ 'enddate' ] = $tests->enddate;
            Mail::to($users->person_address)->send(new RemainCountMail($mailbody));
        }
    }

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
