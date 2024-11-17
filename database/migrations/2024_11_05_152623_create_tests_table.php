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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('params',11);
            $table->string('user_id',128);
            $table->string('testname',512);
            $table->integer('testcount')->length(11);
            $table->integer('nameuseflag')->length(1)->default(1)->nullable();
            $table->integer('genderuseflag')->length(1)->default(1)->nullable();
            $table->integer('mailremaincount')->length(11)->nullable();
            $table->dateTime('startdaytime')->nullable();
            $table->dateTime('enddaytime')->nullable();
            $table->integer('resultflag')->default(1)->nullable();
            $table->integer('envcheckflag')->default(1)->nullable();
            $table->integer('enqflag')->default(1)->nullable();
            $table->integer('lisencedownloadflag')->default(1)->nullable();
            $table->integer('examlistdownloadflag')->default(1)->nullable();
            $table->integer('totaldownloadflag')->default(1)->nullable();
            $table->integer('recomendflag')->default(1)->nullable();
            $table->integer('loginflag')->default(1)->nullable();
            $table->text('logintext')->nullable();
            $table->integer('movietype')->nullable();
            $table->string('moviedisplayurl',512)->nullable();
            $table->integer('pdfuseflag')->default(1)->nullable();
            $table->dateTime('pdfstartday')->nullable();
            $table->dateTime('pdfendday')->nullable();
            $table->integer('pdfcountflag')->default(1)->nullable();
            $table->integer('pdflimitcount')->length(11)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
