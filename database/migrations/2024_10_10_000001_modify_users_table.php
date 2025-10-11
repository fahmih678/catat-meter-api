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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('pam_id')->nullable()->after('id')->index();
            $table->string('phone')->nullable()->after('email');
            $table->softDeletes()->after('password');
            $table->dropColumn('email_verified_at');
            $table->dropColumn('remember_token');

            $table->index(['pam_id', 'name', 'phone', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['pam_id', 'phone']);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });
    }
};
