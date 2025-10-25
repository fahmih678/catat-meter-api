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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pam_id')->constrained('pams')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('meter_reading_id')->constrained('meter_readings')->onDelete('cascade');
            $table->string('bill_number');
            $table->string('reference_number')->nullable();
            $table->decimal('volume_usage', 10, 2);
            $table->decimal('total_bill', 15, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('due_date');
            $table->string('payment_method')->nullable();
            $table->datetime('paid_at')->nullable();
            $table->datetime('issued_at')->nullable();
            $table->string('tariff_snapshot')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['pam_id', 'reference_number']);
            $table->index(['customer_id', 'status', 'paid_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
