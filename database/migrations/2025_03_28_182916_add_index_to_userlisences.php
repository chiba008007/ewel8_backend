<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('userlisences', function (Blueprint $table) {
            //
            // 複合インデックスを追加。名称はLaravelに任せる。
            $table->index(['user_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('userlisences', function (Blueprint $table) {
            //
            // カラム名のみを指定。名称はLaravelに任せる
            $table->dropIndex(['user_id', 'code']);
        });
    }
};
