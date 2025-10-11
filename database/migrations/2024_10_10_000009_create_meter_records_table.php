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
        Schema::create('meter_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pam_id')->constrained('pams')->onDelete('cascade');
            $table->foreignId('meter_id')->constrained('meters')->onDelete('cascade');
            $table->date('period');
            $table->decimal('initial_meter', 10, 2);
            $table->decimal('final_meter', 10, 2);
            $table->decimal('volume_usage', 10, 2);
            $table->string('photo_url')->nullable();
            $table->enum('status', ['draft', 'pending', 'paid'])->default('draft');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['meter_id', 'period']);
            $table->index(['pam_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_records');
    }
};
