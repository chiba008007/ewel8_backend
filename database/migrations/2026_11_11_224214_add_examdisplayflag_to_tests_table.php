<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->integer('examdisplayflag')
                ->default(1)
                ->after('testcount')
                ->comment('検査表示フラグ 1:表示 0:非表示');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('examdisplayflag');
        });
    }
};
