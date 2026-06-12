<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_episode_translations', function (Blueprint $table) {
            // Per-locale SEO overrides; public pages fall back to
            // "{episode title} — {program title}" and the episode description when empty.
            $table->string('seo_title')->nullable()->after('transcript');
            $table->string('seo_description', 500)->nullable()->after('seo_title');
            $table->string('seo_keywords')->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('media_episode_translations', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'seo_keywords']);
        });
    }
};
