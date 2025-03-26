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
        Schema::create('weights', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->default(0)->comment("顧客ID");
            $table->integer('partner_id')->default(0)->comment("パートナーID");
            $table->string('name')->comment("名前");
            for($i=1;$i<=12;$i++){
                $table->string('wt'.$i)->nullable()->comment("wt".$i);
            }
            $table->string('ave')->nullable()->comment("平均");
            $table->string('hensa')->nullable()->comment("偏差");
            $table->integer('status')->default(1)->comment("ステータス");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weights');
    }
};
