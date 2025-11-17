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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->nullable();
            $table->string('account_id')->nullable()->index();
            $table->string('txn_id')->nullable()->index();
            $table->dateTimeTz('timestamp')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('merchant')->nullable();
            $table->text('raw_description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('type', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
