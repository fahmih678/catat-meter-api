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
        // Optimize meter_readings table for filtering and search
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(['registered_month_id', 'status'], 'idx_mr_month_status');
            $table->index(['pam_id', 'status'], 'idx_mr_pam_status');
        });

        // Optimize customers table for search
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['customer_number'], 'idx_cust_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex('idx_mr_month_status');
            $table->dropIndex('idx_mr_pam_status');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_cust_number');
        });
    }
};
