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
            $table->string('provider_id', 1000)->nullable()->change();
            $table->string('provider_name', 1000)->nullable()->change();
            $table->string('provider_token', 1000)->nullable()->change();
            $table->string('provider_refresh_token', 1000)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider_id', 255)->nullable()->change();
            $table->string('provider_name', 255)->nullable()->change();
            $table->string('provider_token', 255)->nullable()->change();
            $table->string('provider_refresh_token', 255)->nullable()->change();
        });
    }
};
