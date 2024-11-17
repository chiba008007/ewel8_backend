<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Http\Controllers\ExamController;
use Illuminate\Database\Seeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\ProductTableSeeder;
use Database\Seeders\PrefecturesTableSeeder;
use Database\Seeders\ElementsTable;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(UsersTableSeeder::class);
        $this->call(ProductTableSeeder::class);
        $this->call(PrefecturesTableSeeder::class);
        $this->call(ElementsTable::class);
        $this->call(ExamsTable::class);
    }
}
