<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ElementsTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('elements')->delete();
        DB::table('elements')->insert([
            [
                'code' => '1',
                'note' => '自己感情モニタリング力',
            ],
            [
                'code' => '2',
                'note' => '客観的自己評価力',
            ],
            [
                'code' => '3',
                'note' => '自己肯定力',
            ],
            [
                'code' => '4',
                'note' => 'コントロール＆アチーブメント力',
            ],
            [
                'code' => '5',
                'note' => 'ビジョン創出力',
            ],
            [
                'code' => '6',
                'note' => 'ポジティブ思考力',
            ],
            [
                'code' => '7',
                'note' => '対人共感力',
            ],
            [
                'code' => '8',
                'note' => '状況察知力',
            ],
            [
                'code' => '9',
                'note' => 'ホスピタリティ発揮力',
            ],
            [
                'code' => '10',
                'note' => 'リーダーシップ発揮力',
            ],
            [
                'code' => '11',
                'note' => 'アサーション発揮力',
            ],
            [
                'code' => '12',
                'note' => '集団適応力',
            ],
            [
                'code' => '13',
                'note' => '平均点',
            ],
            [
                'code' => '14',
                'note' => '標準偏差値',
            ],
        ]);
    }
}
