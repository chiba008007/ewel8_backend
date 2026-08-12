<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('exam_baj4s', function (Blueprint $table) {
            // 主キー
            $table->bigIncrements('id');

            // 関連ID
            $table->unsignedBigInteger('testparts_id')->nullable();
            $table->unsignedBigInteger('exam_id')->nullable();

            // 開始・終了日時
            $table->timestamp('starttime')->nullable();
            $table->timestamp('endtime')->nullable();

            // 設問 q1 ～ q36
            for ($i = 1; $i <= 36; $i++) {
                $table->integer('q' . $i)->nullable();
            }

            // dev1 ～ dev12
            for ($i = 1; $i <= 12; $i++) {
                $table->string('dev' . $i, 255)->nullable();
            }

            // その他
            $table->string('soyo', 255)->nullable();
            $table->string('level', 255)->nullable();
            $table->string('score', 255)->nullable();
            $table->integer('status')->nullable();

            // created_at / updated_at もNULL許容
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            // 検索用インデックス
            $table->index(
                ['exam_id', 'status', 'id'],
                'exam_baj4s_exam_status_id_idx'
            );
        });
    }

    public function down(): void
    {
        // ロールバック時にテーブル削除
        Schema::dropIfExists('exam_baj4s');
    }
};
