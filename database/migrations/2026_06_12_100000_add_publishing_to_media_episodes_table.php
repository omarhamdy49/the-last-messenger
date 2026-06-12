<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_episodes', function (Blueprint $table) {
            // Default true so every pre-existing episode stays publicly visible.
            $table->boolean('is_published')->default(true)->after('is_featured');
            $table->timestamp('publish_at')->nullable()->after('is_published');

            // Covers the hourly publisher query: is_published = 0 AND publish_at <= now.
            $table->index(['is_published', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::table('media_episodes', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'publish_at']);
            $table->dropColumn(['is_published', 'publish_at']);
        });
    }
};
