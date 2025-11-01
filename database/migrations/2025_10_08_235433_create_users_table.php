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
        Schema::create('bills_list', function (Blueprint $table) {
            $table->id();

            // 外部キー（bills.id と関連付け）
            $table->foreignId('bill_id')
                ->constrained('bills')
                ->onDelete('cascade'); // 親が削除されたら明細も削除

            // 明細情報
            $table->integer('number')->nullable();             // 行番号など
            $table->string('title', 255)->nullable();          // 品目名・タイトル
            $table->string('name', 255)->nullable();           // 詳細名
            $table->string('kikaku', 255)->nullable();         // 規格・仕様
            $table->integer('quantity')->default(1);           // 数量
            $table->string('unit', 50)->nullable();            // 単位（個、式など）
            $table->decimal('money', 12, 2)->default(0);       // 金額

            // 作成・更新日時
            $table->timestamp('create_ts')->useCurrent();
            $table->timestamp('update_ts')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills_list');
    }
};
