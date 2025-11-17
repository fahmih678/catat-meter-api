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
        Schema::table('registered_months', function (Blueprint $table) {
            $table->decimal('total_payment', 15, 2)->default(0)->after('total_bills');
            $table->integer('total_paid_customers')->default(0)->after('total_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registered_months', function (Blueprint $table) {
            $table->dropColumn(['total_payment', 'total_paid_customers']);
        });
    }
};
