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
        Schema::create('exam_baj3s', function (Blueprint $table) {
            $table->id();

            // 外部キー想定
            $table->foreignId('testparts_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();

            $table->timestamp("starttime")->nullable();
            $table->timestamp("endtime")->nullable();
            for($i=1;$i<=36;$i++){
                $q = "q".$i;
                $table->integer($q)->nullable();
            }
            for($i=1;$i<=12;$i++){
                $dev = "dev".$i;
                $table->string($dev)->nullable();
            }
            $table->string("soyo")->nullable();
            $table->string("level")->nullable();
            $table->string("score")->nullable();
            $table->integer("status")->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_baj3s');
    }
};
