<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment("admin/partner/test");
            $table->integer('admin_id')->default(0)->comment("親ID");
            $table->integer('partner_id')->default(0)->comment("パートナーID");
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('company_name',128)->nullable()->comment("企業名");
            $table->string('login_id',128)->unique()->nullable()->comment("ログインID");
            $table->string('post_code',128)->nullable()->comment("郵便番号");
            $table->string('pref',11)->nullable()->comment("都道府県");
            $table->string('address1',256)->nullable()->comment("住所1");
            $table->string('address2',256)->nullable()->comment("住所2");
            $table->string('tel',128)->nullable()->comment("電話番号");
            $table->string('fax',128)->nullable()->comment("FAX番号");
            $table->integer('requestFlag')->length(1)->nullable()->comment("申し込み検査ボタン");
            $table->string('person',256)->nullable()->comment("担当者氏名");
            $table->string('person_address',256)->nullable()->comment("担当者アドレス");
            $table->string('person2',256)->nullable()->comment("担当者氏名2");
            $table->string('person_address2',256)->nullable()->comment("担当者アドレス2");
            $table->string('person_tel',128)->nullable()->comment("担当者連絡先");
            $table->string('system_name',256)->nullable()->comment("システム名");
            $table->string('element1',256)->nullable()->comment("要素");
            $table->string('element2',256)->nullable()->comment("要素");
            $table->string('element3',256)->nullable()->comment("要素");
            $table->string('element4',256)->nullable()->comment("要素");
            $table->string('element5',256)->nullable()->comment("要素");
            $table->string('element6',256)->nullable()->comment("要素");
            $table->string('element7',256)->nullable()->comment("要素");
            $table->string('element8',256)->nullable()->comment("要素");
            $table->string('element9',256)->nullable()->comment("要素");
            $table->string('element10',256)->nullable()->comment("要素");
            $table->string('element11',256)->nullable()->comment("要素");
            $table->string('element12',256)->nullable()->comment("要素");
            $table->integer('trendFlag')->nullable()->comment("受検者傾向確認ボタン表示");
            $table->integer('csvFlag')->nullable()->comment("CSVアップロードボタン表示");
            $table->integer('pdfFlag')->nullable()->comment("PDFボタン表示");
            $table->integer('weightFlag')->nullable()->comment("PDF重みマスタ表示");
            $table->integer('excelFlag')->nullable()->comment("エクセル重みマスタ表示");
            $table->integer('customFlag')->nullable()->comment("顧客ファイルアップロード表示");
            $table->integer('sslFlag')->nullable()->comment("SSL設定");
            $table->string('logoImagePath',512)->nullable()->comment("アップロード画像選択");
            $table->integer('privacy')->nullable()->comment("プライバシーポリシー表示 1:デフォルト表示 2:編集表示");
            $table->longText('privacyText')->nullable()->comment("プライバシーポリシー編集テキスト");
            $table->integer('displayFlag')->nullable()->comment("顧客の表示/非表示");
            $table->string('tanto_name',512)->nullable()->comment("担当者氏名");
            $table->string('tanto_address',512)->nullable()->comment("担当者アドレス");
            $table->string('tanto_busyo',512)->nullable()->comment("部署名");
            $table->string('tanto_tel1',512)->nullable()->comment("連絡先1");
            $table->string('tanto_tel2',512)->nullable()->comment("連絡先2");
            $table->string('tanto_name2',512)->nullable()->comment("担当者氏名2");
            $table->string('tanto_address2',512)->nullable()->comment("担当者アドレス2");

            $table->rememberToken();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
