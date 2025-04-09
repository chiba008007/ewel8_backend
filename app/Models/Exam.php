<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class Exam extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
     //   'password' => 'hashed',
    ];

    protected $rules = ['email' => 'required|unique'];

    public static function getbuldInsertKey(){

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
    public static function bulkInsert($data) {

        $key = implode(",",self::getbuldInsertKey());
        $sql = "INSERT INTO exams (".$key.") VALUES ";
        $aline = [];
        foreach($data as $value){
            $sql .= "(";
            $aimp = [];
            foreach($value as $val){
                $aimp[]= "'?'";
                $aline[] = $val;
            }
            $implode = implode(",", $aimp);
            $sql .= $implode."),";
        }
        $sql = preg_replace("/,$/","",$sql);

        try {
                return DB::insert($sql, $aline);
        }catch(\Exception $e){
            echo "error\n";
            var_dump($e);
        }

        return true;
    }
    public static function setEndTime(){
        $loginUser = auth()->user()->currentAccessToken();
        $todo = Exam::find($loginUser->tokenable->id);
        // 受検を行うテストの総数と受検済みの数が同じときにテスト時間の更新
        $testcount = testparts::where([
            "test_id"=>$todo->test_id,
            "status"=>1,
        ])->count();
        $examfin = examfins::where("exam_id",$loginUser->tokenable->id)->count();
        if(!$todo->ended_at && $testcount === $examfin){
            $todo->ended_at = date("Y-m-d H:i:s");
            $todo->save();
        }
    }
}
