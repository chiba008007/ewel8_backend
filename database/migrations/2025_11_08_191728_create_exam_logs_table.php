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
        Schema::create('exam_logs', function (Blueprint $table) {
            $table->id();

            // 追加カラム
            $table->string('code')->nullable();               // 検査コードなど
            $table->unsignedBigInteger('test_id')->nullable();
            $table->unsignedBigInteger('testparts_id')->nullable();
            $table->string('exam_id');                        // 受検ID（文字列型）
            $table->tinyInteger('status')->default(1);        // 0:なし 1:実施中 2:完了

            // 日時情報
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_logs');
    }
};
