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
        Schema::create('fixed_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pam_id')->constrained('pams')->onDelete('cascade');
            $table->foreignId('tariff_group_id')->constrained('tariff_groups')->onDelete('cascade');
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['pam_id', 'tariff_group_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_fees');
    }
};
