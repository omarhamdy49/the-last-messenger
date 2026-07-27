<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin listing sorts companions by display name per-locale (see
 * SahabiRepository::applyLocaleNameOrder) and the new admin filters query
 * by locale. The standalone `locale` index only serves locale-only lookups;
 * replace it with a composite (locale, name) index so both the locale
 * filter and the name ordering within that locale are index-assisted.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sahabi_translations', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->index(['locale', 'name'], 'sahabi_translations_locale_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sahabi_translations', function (Blueprint $table) {
            $table->dropIndex('sahabi_translations_locale_name_index');
            $table->index('locale');
        });
    }
};
