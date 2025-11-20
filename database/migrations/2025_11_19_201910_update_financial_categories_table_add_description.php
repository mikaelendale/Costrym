<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns to drop exist
        $columnsToCheck = ['user_id', 'type', 'parent_id', 'color', 'icon', 'ai_keywords'];
        $columnsToDrop = [];
        
        foreach ($columnsToCheck as $column) {
            if (Schema::hasColumn('financial_categories', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        Schema::table('financial_categories', function (Blueprint $table) use ($columnsToDrop) {
            // Drop old columns if they exist
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Add new columns if they don't exist
            if (!Schema::hasColumn('financial_categories', 'description')) {
                $table->text('description')->after('name');
            }
            if (!Schema::hasColumn('financial_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
            if (!Schema::hasColumn('financial_categories', 'display_order')) {
                $table->integer('display_order')->default(0)->after('is_active');
            }
        });

        // Add indexes if they don't exist
        if (!Schema::hasIndex('financial_categories', 'financial_categories_name_index')) {
            Schema::table('financial_categories', function (Blueprint $table) {
                $table->index('name');
            });
        }
        
        if (!Schema::hasIndex('financial_categories', 'financial_categories_is_active_index')) {
            Schema::table('financial_categories', function (Blueprint $table) {
                $table->index('is_active');
            });
        }

        // Make name unique
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS financial_categories_name_unique ON financial_categories (name)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_categories', function (Blueprint $table) {
            // Restore old columns
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->json('ai_keywords')->nullable();

            // Drop new columns
            $table->dropColumn(['description', 'is_active', 'display_order']);

            // Drop indexes
            $table->dropIndex(['name']);
            $table->dropIndex(['is_active']);
        });

        DB::statement('DROP INDEX IF EXISTS financial_categories_name_unique');
    }
};
