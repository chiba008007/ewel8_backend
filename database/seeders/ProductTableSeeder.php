<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('products')->delete();
        DB::table('products')->insert([
            [
            'title' => '製品1',
            'description' => 'test1',
            ],
            [
            'title' => '製品2',
            'email' => 'test2',
            ]
        ]
        );
    }
}
