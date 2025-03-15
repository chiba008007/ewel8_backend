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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->integer('test_id');
            $table->integer('customer_id');
            $table->integer('partner_id');
            $table->string('param')->comment('テストのパラメータ');
            $table->string('name')->nullable();
            $table->string('kana')->nullable();
            $table->integer('gender')->default(0)->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable()->comment('デフォルトパスワード:password');
            $table->rememberToken();
            $table->integer('passflag')->default(0)->nullable();
            $table->text('memo1')->nullable();
            $table->text('memo2')->nullable();
            $table->timestamp('started_at')->nullable()->comment('全部試験開始日時');
            $table->timestamp('ended_at')->nullable()->comment('全部試験終了日時');
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
        Schema::dropIfExists('exams');
    }
};
