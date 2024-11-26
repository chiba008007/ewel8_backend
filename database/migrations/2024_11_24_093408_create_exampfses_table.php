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
        Schema::create('exampfses', function (Blueprint $table) {
            $table->id();
            $table->integer("testparts_id")->length(11);
            $table->integer("exam_id")->length(11);
            $table->timestamp("starttime")->nullable();
            $table->timestamp("endtime")->nullable();
            for($i=1;$i<=36;$i++){
                $q = "q".$i;
                $table->integer($q)->nullable()->length(11);
            }
            for($i=1;$i<=12;$i++){
                $dev = "dev".$i;
                $table->string($dev)->nullable();
            }
            $table->string("soyo")->nullable();
            $table->string("level")->nullable();
            $table->string("score")->nullable();
            $table->integer("status")->default(1);

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exampfses');
    }
};
