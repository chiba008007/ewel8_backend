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
            $table->string('user_id',128);
            $table->string('testname',512);
            $table->integer('testcount')->length(11);
            $table->integer('nameuseflag')->length(1)->default(1);
            $table->integer('genderuseflag')->length(1)->default(1);
            $table->integer('mailremaincount')->length(11);
            $table->dateTime('startdaytime');
            $table->dateTime('enddaytime');
            $table->integer('resultflag')->default(1);
            $table->integer('envcheckflag')->default(1);
            $table->integer('enqflag')->default(1);
            $table->integer('lisencedownloadflag')->default(1);
            $table->integer('examlistdownloadflag')->default(1);
            $table->integer('totaldownloadflag')->default(1);
            $table->integer('recomendflag')->default(1);
            $table->integer('loginflag')->default(1);
            $table->text('logintext');
            $table->integer('movietype');
            $table->string('moviedisplayurl',512);
            $table->integer('pdfuseflag')->default(1);
            $table->dateTime('pdfstartday');
            $table->dateTime('pdfendday');
            $table->integer('pdfcountflag')->default(1);
            $table->integer('pdflimitcount')->length(11);
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
