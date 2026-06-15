<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A translation resource is one translation work (e.g. Saheeh International,
 * Pickthall, Hamidullah) so a verse can carry several translations per language
 * the way quran.com does — mirroring quran_tafsir_resources. The Arabic Quran
 * text itself is modelled as the `ar-uthmani` resource. quran_verse_translations
 * .resource_id points here; exactly one resource per language is `is_default`,
 * which the legacy single-translation read path uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_translation_resources', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->json('name'); // translatable {ar:..., en:...}
            $table->string('author', 255)->nullable();
            $table->string('language', 5)->default('ar'); // ar, en, fr, tr, ur (en-tl = transliteration)
            $table->string('direction', 3)->default('ltr'); // rtl for ar/ur, ltr otherwise
            $table->string('source', 255)->nullable();
            $table->boolean('is_default')->default(false); // canonical edition for its language
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('language');
            $table->index(['language', 'is_default']);
            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_translation_resources');
    }
};
