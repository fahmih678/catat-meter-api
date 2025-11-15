<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing performance indexes based on query analysis:
     * - Customers table: pam_id + is_active filtering
     * - Meters table: customer_id + is_active filtering
     * - Bills table: status + pam_id filtering
     */
    public function up(): void
    {
        // Optimize customers table for PAM and active status filtering
        Schema::table('customers', function (Blueprint $table) {
            // Critical for CustomerController::unrecordedList() query
            $table->index(['pam_id', 'is_active'], 'idx_customers_pam_active');
        });

        // Optimize meters table for customer and active status filtering
        Schema::table('meters', function (Blueprint $table) {
            // Critical for finding active meters for customers
            $table->index(['customer_id', 'is_active'], 'idx_meters_customer_active');
        });

        // Optimize bills table for status and PAM filtering
        Schema::table('bills', function (Blueprint $table) {
            // Critical for BillController and payment filtering
            $table->index(['status', 'pam_id'], 'idx_bills_status_pam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_pam_active');
        });

        Schema::table('meters', function (Blueprint $table) {
            $table->dropIndex('idx_meters_customer_active');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('idx_bills_status_pam');
        });
    }
};
