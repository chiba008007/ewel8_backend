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
        Schema::create('pdf_output_cron_logs', function (Blueprint $table) {
            $table->id();

            // 対象識別
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('test_id');


            // PDF種別
            $table->enum('type', ['individual', 'merged'])
                ->comment('individual: 個別PDF(ZIP), merged: 結合PDF');

            // 進捗管理
            $table->unsignedInteger('total_count');
            $table->unsignedInteger('processed_count')->default(0);

            // 状態
            $table->enum('status', ['pending', 'processing', 'done', 'error'])
                ->default('pending');

            // 出力結果
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // よく使う検索用
            $table->index(['test_id', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_output_cron_logs');
    }
};
