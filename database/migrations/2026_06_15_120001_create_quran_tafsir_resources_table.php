<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A tafsir resource is one tafsir work (e.g. Al-Muyassar, Ibn Kathir), so a verse
 * can carry several tafsirs the way quran.com does. quran_verse_tafsirs.resource_id
 * points here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_tafsir_resources', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->json('name'); // translatable {ar:..., en:...}
            $table->string('author', 255)->nullable();
            $table->string('language', 5)->default('ar'); // primary language of the tafsir
            $table->string('source', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('language');
            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_tafsir_resources');
    }
};
