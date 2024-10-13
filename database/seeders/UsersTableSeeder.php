<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            'cost' => 12,
        ];
        //
        DB::table('users')->delete();
        DB::table('users')->insert([
            [
            'type' => 'admin',
            'admin_id'=>0,
            'partner_id'=>0,
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => password_hash('password',PASSWORD_BCRYPT, $options),
            'company_name'=>'サンプル企業',
            'login_id'=>'sample',
            'post_code'=>'063-0123',
            'pref'=>'北海道',
            'address1'=>'サンプル札幌',
            'address2'=>'サンプル住所2',
            'tel'=>'090-1234-1234',
            'fax'=>'000-1111-1111',
            'requestFlag'=>0,
            'person'=>'担当者1',
            'person_address'=>'tanto@sample.com',
            'person2'=>'担当者2',
            'person_address2'=>'tanto2@sample.com',
            'person_tel'=>'090-0000-0000',
            'system_name'=>'さんぷるず',
            ],
            [
            'type' => 'admin',
            'admin_id'=>0,
            'partner_id'=>0,
            'name' => 'admin1',
            'email' => 'admin@admin.com',
            'password' => password_hash('password',PASSWORD_BCRYPT, $options),
            'company_name'=>'サンプル企業admin',
            'login_id'=>'admin2',
            'post_code'=>'063-0123',
            'pref'=>'東京都',
            'address1'=>'サンプル札幌',
            'address2'=>'サンプル住所2',
            'tel'=>'090-1234-1234',
            'fax'=>'000-1111-1111',
            'requestFlag'=>0,
            'person'=>'担当者21',
            'person_address'=>'tanto21@sample.com',
            'person2'=>'担当者22',
            'person_address2'=>'tanto22@sample.com',
            'person_tel'=>'090-0000-0000',
            'system_name'=>'さんぷるず',
            ],
            [
            'type' => 'admin',
            'admin_id'=>0,
            'partner_id'=>0,
            'name' => 'admin3',
            'email' => 'admin3@admin.com',
            'password' => password_hash('password',PASSWORD_BCRYPT, $options),
            'company_name'=>'サンプル企業admin',
            'login_id'=>'admin3',
            'post_code'=>'063-0123',
            'pref'=>'神奈川県',
            'address1'=>'サンプル札幌',
            'address2'=>'サンプル住所2',
            'tel'=>'090-1234-1234',
            'fax'=>'000-1111-1111',
            'requestFlag'=>0,
            'person'=>'担当者31',
            'person_address'=>'tanto32@sample.com',
            'person2'=>'担当者32',
            'person_address2'=>'tanto32@sample.com',
            'person_tel'=>'090-0000-0000',
            'system_name'=>'さんぷるず',
            ],
            [
            'type' => 'admin',
            'admin_id'=>0,
            'partner_id'=>0,
            'name' => 'admin4',
            'email' => 'admin4@admin.com',
            'password' => password_hash('password',PASSWORD_BCRYPT, $options),
            'company_name'=>'サンプル企業admin',
            'login_id'=>'admin4',
            'post_code'=>'063-0123',
            'pref'=>'宮城県',
            'address1'=>'サンプル札幌',
            'address2'=>'サンプル住所2',
            'tel'=>'090-1234-1234',
            'fax'=>'000-1111-1111',
            'requestFlag'=>0,
            'person'=>'担当者41',
            'person_address'=>'tanto41@sample.com',
            'person2'=>'担当者42',
            'person_address2'=>'tanto42@sample.com',
            'person_tel'=>'090-0000-0000',
            'system_name'=>'さんぷるず',
            ],
            [
            'type' => 'partner',
            'admin_id'=>1,
            'partner_id'=>0,
            'name' => 'John Doe2',
            'email' => 'john2@doe.com',
            'password' => password_hash('password',PASSWORD_BCRYPT, $options),
            'company_name'=>'サンプル企業',
            'login_id'=>'sample2',
            'post_code'=>'063-0123',
            'pref'=>'東京都',
            'address1'=>'サンプル札幌',
            'address2'=>'サンプル住所2',
            'tel'=>'090-1234-1234',
            'fax'=>'000-1111-1111',
            'requestFlag'=>0,
            'person'=>'担当者1',
            'person_address'=>'tanto@sample.com',
            'person2'=>'担当者2',
            'person_address2'=>'tanto2@sample.com',
            'person_tel'=>'090-0000-0000',
            'system_name'=>'さんぷるず',
            ]
        ]);
    }
}
