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
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pam_id')->constrained('pams')->onDelete('cascade');
            $table->foreignId('meter_id')->constrained('meters')->onDelete('cascade');
            $table->foreignId('registered_month_id')->constrained('registered_months')->onDelete('cascade');
            $table->decimal('previous_reading', 10, 2);
            $table->decimal('current_reading', 10, 2);
            $table->decimal('volume_usage', 10, 2);
            $table->string('photo_url')->nullable();
            $table->enum('status', ['draft', 'pending', 'paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('reading_by')->constrained('users')->onDelete('cascade');
            $table->date('reading_at');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['meter_id', 'registered_month_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
