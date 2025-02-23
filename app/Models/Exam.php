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
        // $sql = "INSERT INTO exams (test_id,customer_id,partner_id,param,type,email)VALUES(?,?,?,?,?,?) ";
        // $aline = [1,1,1,'aaa','bbb','ccc'];
        // $flg = DB::insert($sql, $aline);
        return DB::insert($sql, $aline);
}catch(\Exception $e){
    echo "error\n";
    var_dump($e);
}

return true;
//        return DB::insert($sql, $aline);
    }
}
