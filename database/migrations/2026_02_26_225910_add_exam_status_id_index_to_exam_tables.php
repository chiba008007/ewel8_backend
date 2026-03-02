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
        Schema::table('exampfses', function (Blueprint $table) {
            $table->index(
                ['exam_id', 'status', 'id'],
                'exampfses_exam_status_id_idx'
            );
        });

        Schema::table('exam_baj3s', function (Blueprint $table) {
            $table->index(
                ['exam_id', 'status', 'id'],
                'exam_baj3s_exam_status_id_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exampfses', function (Blueprint $table) {
            $table->dropIndex('exampfses_exam_status_id_idx');
        });

        Schema::table('exam_baj3s', function (Blueprint $table) {
            $table->dropIndex('exam_baj3s_exam_status_id_idx');
        });
    }
};
