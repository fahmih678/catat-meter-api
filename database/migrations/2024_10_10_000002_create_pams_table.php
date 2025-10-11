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
        Schema::create('pams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('code')->unique();
            $table->string('logo_url')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->json('coordinate')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pams');
    }
};
