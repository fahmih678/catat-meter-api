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
        // Add composite index for store() function optimization
        Schema::table('registered_months', function (Blueprint $table) {
            $table->index(['pam_id', 'period'], 'idx_pam_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registered_months', function (Blueprint $table) {
            $table->dropIndex('idx_pam_period');
        });
    }
};
