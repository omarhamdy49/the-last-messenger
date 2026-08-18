<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-01: article_locale has no unique(locale, slug), so two articles can
 * collide on the same locale-scoped slug. Dedup (keep the lowest id per
 * pair) before adding the index, since a duplicate would otherwise fail
 * index creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DELETE t1 FROM article_locale t1
            INNER JOIN article_locale t2
                ON t1.locale = t2.locale
               AND t1.slug = t2.slug
               AND t1.id > t2.id
        SQL);

        Schema::table('article_locale', function (Blueprint $table) {
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('article_locale', function (Blueprint $table) {
            $table->dropUnique(['locale', 'slug']);
        });
    }
};
