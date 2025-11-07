<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enhances categories table with nested set model and additional fields
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Nested Set Model fields for hierarchical structure
            $table->unsignedInteger('lft')->default(0)->after('parent_id');
            $table->unsignedInteger('rgt')->default(0)->after('lft');
            $table->unsignedInteger('depth')->default(0)->after('rgt');

            // Status field for active/inactive categories
            $table->enum('status', ['active', 'inactive'])->default('active')->after('type');

            // Soft deletes support
            $table->softDeletes();

            // Additional indexes for nested set queries
            $table->index(['lft', 'rgt']);
            $table->index('depth');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['lft', 'rgt', 'depth', 'status', 'deleted_at']);
            $table->dropIndex(['lft', 'rgt']);
            $table->dropIndex(['depth']);
            $table->dropIndex(['status']);
        });
    }
};
