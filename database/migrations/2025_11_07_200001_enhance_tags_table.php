<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enhances tags table with additional fields and soft deletes
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // Add description field for multi-language support
            $table->json('description')->nullable()->after('name');

            // Add color field for tag visualization
            $table->string('color', 7)->nullable()->after('type');

            // Add cached usage count for performance
            $table->unsignedInteger('usage_count')->default(0)->after('color');

            // Soft deletes support
            $table->softDeletes();

            // Additional indexes
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['description', 'color', 'usage_count', 'deleted_at']);
            $table->dropIndex(['usage_count']);
        });
    }
};
