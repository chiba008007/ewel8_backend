<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'email',
        'password',
        'company_name',
        'login_id',
        'post_code',
        'pref',
        'address1',
        'address2',
        'tel',
        'fax',
        'requestFlag',
        'person',
        'person_address',
        'person2',
        'person_address2',
        'person_tel',
        'system_name',
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
        'password' => 'hashed',
    ];

    protected $rules = ['email' => 'required|unique','login_id'=>'required|unique'];



    /**
     * PassportやSanctumがユーザーを認証する際に使用するメソッド。
     *
     * @param  string  $identifier
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function findForPassport($identifier)
    {
        // ここで、認証に使用するフィールドを変更します
        // 例えば、usernameを使用する場合
        return static::where('login_id', $identifier)->first();
    }

    public static function getDetail($id)
    {
        $result = DB::table("users")->find($id);
        return $result;
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'partner_id');
    }

    public function userLicenses()
    {
        return $this->hasMany(UserLisence::class, 'user_id');
    }

}
