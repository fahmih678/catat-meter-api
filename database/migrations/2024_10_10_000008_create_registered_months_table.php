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
        Schema::create('registered_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pam_id')->constrained('pams')->onDelete('cascade');
            $table->string('period'); // Format: YYYY-MM
            $table->integer('total_customers');
            $table->decimal('total_usage', 15, 2);
            $table->decimal('total_bills', 15, 2);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['pam_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registered_months');
    }
};
