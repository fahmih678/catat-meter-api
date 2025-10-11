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
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pam_id')->constrained('pams')->onDelete('cascade');
            $table->string('month'); // Format: YYYY-MM
            $table->integer('total_customers')->comment('jumlah pelanggan aktif');
            $table->decimal('total_volume', 15, 2)->comment('total m³ air terpakai');
            $table->decimal('total_income', 15, 2)->comment('total tagihan dibayar');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['pam_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
