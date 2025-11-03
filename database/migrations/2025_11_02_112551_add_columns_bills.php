<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            //
            $table->boolean('open_status')
                ->default(false)
                ->after('note')
                ->comment('開封状態: false=未開封, true=開封済み');

            $table->boolean('status')
                ->default(true)
                ->after('open_status')
                ->comment('請求書状態: true=有効, false=無効');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            //
        });
    }
};
