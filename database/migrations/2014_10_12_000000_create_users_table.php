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
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('company_name',128)->nullable()->comment("企業名");
            $table->string('login_id',128)->unique()->nullable()->comment("ログインID");
            $table->string('post_code',128)->nullable()->comment("郵便番号");
            $table->string('pref',2)->nullable()->comment("都道府県");
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
