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
        // Only add the column if the table exists and the column is missing
        if (Schema::hasTable('category_models') && ! Schema::hasColumn('category_models', 'expenses')) {
            Schema::table('category_models', function (Blueprint $table) {
                $table->json('expenses')->nullable()->after('meta_data');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('category_models') && Schema::hasColumn('category_models', 'expenses')) {
            Schema::table('category_models', function (Blueprint $table) {
                $table->dropColumn('expenses');
            });
        }
    }
};
