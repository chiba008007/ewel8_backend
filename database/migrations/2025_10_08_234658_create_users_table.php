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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('post', 10)->nullable();                // 郵便番号
            $table->string('address_1', 255)->nullable();          // 住所1
            $table->string('address_2', 255)->nullable();          // 住所2
            $table->string('company_name', 255)->nullable();       // 会社名
            $table->string('busyo', 255)->nullable();              // 部署
            $table->string('yakusyoku', 255)->nullable();          // 役職
            $table->string('name', 100)->nullable();               // 担当者名

            // 金額・請求情報
            $table->decimal('money', 12, 2)->default(0);           // 金額
            $table->string('title', 255)->nullable();              // 件名
            $table->date('pay_date')->nullable();                  // 支払期日
            $table->string('pay_bank', 255)->nullable();           // 振込銀行
            $table->string('pay_number', 50)->nullable();          // 口座番号
            $table->string('pay_name', 100)->nullable();           // 振込名義

            // 請求書情報
            $table->string('bill_number', 50)->nullable()->index(); // 請求書番号（検索用にindex）
            $table->date('bill_date')->nullable();                 // 発行日

            // 請求元情報
            $table->string('from_post', 10)->nullable();           // 請求元 郵便番号
            $table->string('from_address_1', 255)->nullable();     // 請求元 住所1
            $table->string('from_address_2', 255)->nullable();     // 請求元 住所2
            $table->string('from_name', 100)->nullable();          // 請求元 担当者名
            $table->string('from_tel', 20)->nullable();            // 請求元 電話番号

            // フラグ
            $table->boolean('company_print_flag')->default(false); // 会社印刷フラグ
            $table->boolean('tanto_print_flag')->default(false);   // 担当印刷フラグ

            // 備考
            $table->text('note')->nullable();

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
        Schema::dropIfExists('bills');
    }
};
