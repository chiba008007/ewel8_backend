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
            $table->float('sougo')->nullable()->after('score');
            $table->float('personal')->nullable()->after('sougo');
            $table->float('state')->nullable()->after('personal');
            $table->float('job')->nullable()->after('state');
            $table->float('image')->nullable()->after('job');
            $table->float('positive')->nullable()->after('image');
            $table->float('self')->nullable()->after('positive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exampfses', function (Blueprint $table) {
            $table->dropColumn([
                'sougo',
                'personal',
                'state',
                'job',
                'image',
                'positive',
                'self'
            ]);
        });
    }
};
