<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale label for hero quick-filter chips.
 *
 * The quick_filter_chips migration (2026_06_10_120004) already documents this
 * table as the home of the translatable chip label; it is created here so the
 * chips can be fully admin-managed in all five locales.
 *
 * Index names are explicit: the default unique name would exceed MySQL's
 * 64-character identifier limit. The table may already exist on environments
 * where it was created out-of-band (without indexes) — that case is
 * reconciled instead of failing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quick_filter_chip_translations')) {
            Schema::create('quick_filter_chip_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quick_filter_chip_id')
                    ->constrained('quick_filter_chips')
                    ->cascadeOnDelete();
                $table->string('locale', 5)->index('qfc_translations_locale_index');
                $table->string('label', 255)->nullable();
                $table->boolean('is_ready')->default(false);
                $table->timestamps();

                $table->unique(['quick_filter_chip_id', 'locale'], 'qfc_translations_chip_locale_unique');
            });

            return;
        }

        // Table pre-exists without the locale index + unique pair — add them.
        Schema::table('quick_filter_chip_translations', function (Blueprint $table) {
            if (! Schema::hasIndex('quick_filter_chip_translations', 'qfc_translations_locale_index')) {
                $table->index('locale', 'qfc_translations_locale_index');
            }

            if (! Schema::hasIndex('quick_filter_chip_translations', 'qfc_translations_chip_locale_unique')) {
                $table->unique(['quick_filter_chip_id', 'locale'], 'qfc_translations_chip_locale_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_filter_chip_translations');
    }
};
