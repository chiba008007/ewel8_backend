<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamsTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $key = 'ewel_secret_key';
        $iv = "1234567890123456";
        //
        DB::table('exams')->truncate();
        DB::table('exams')->insert([
            [
            'type' => 'PFS',
            'test_id'=>1,
            'customer_id'=>1,
            'partner_id'=>1,
            'param'=>'aaaaaa',
            'name' => 'John Test',
            'email' => 'john',
            'password' => openssl_encrypt('Test', 'aes-256-cbc', $key, 0, $iv)
            ],
        ]);
    }
}
