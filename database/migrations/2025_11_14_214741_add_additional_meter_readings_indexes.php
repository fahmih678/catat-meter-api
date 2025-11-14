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
        // Add additional indexes specifically for MeterReadingController optimization
        Schema::table('meter_readings', function (Blueprint $table) {
            // Composite index for primary filtering (replaces idx_mr_pam_status)
            $table->index(['pam_id', 'registered_month_id', 'status'], 'idx_pam_month_status');

            // Critical indexes for JOIN operations
            $table->index(['reading_by'], 'idx_reading_by');

            // Critical index for sorting and pagination
            $table->index(['reading_at'], 'idx_reading_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex('idx_pam_month_status');
            $table->dropIndex('idx_reading_by');
            $table->dropIndex('idx_reading_at');
        });
    }
};
