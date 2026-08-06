<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §2 — the application-wide person identity.
 *
 * One row per historical figure regardless of how many capacities they appear
 * in. Hadith narrators, collection compilers, companions, scholars, graders,
 * and genealogy nodes are all rows in this table.
 *
 * Descent lives here as two self-referencing columns rather than as rows in a
 * relationship table: one father and one mother is then enforced by the column
 * itself. Everything descent cannot express goes to `muslim_relationships`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muslims', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 200)->unique();

            // Scalar portrait only. `Muslim` deliberately does NOT implement
            // Spatie HasMedia — companion galleries are not carried forward
            // (Owner Decision 4).
            $table->string('thumb', 255)->nullable();

            // Both nullable: source records legitimately carry only one
            // language. The CHECK below forbids only the nameless row.
            $table->string('name_ar', 200)->nullable();
            $table->string('name_en', 200)->nullable();

            // Spatie Translatable JSON, {ar,en} only (Decision D).
            $table->json('kunya')->nullable();
            $table->json('bio')->nullable();
            $table->json('content')->nullable();
            $table->json('birth_place')->nullable();

            $table->string('gender', 10)->nullable();

            // SIGNED on purpose: pre-Hijra years are negative, and a companion
            // born before the Hijra is the common case, not the edge case.
            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable();

            $table->string('generation', 50)->nullable();

            // Normalised copies for FULLTEXT. Built from every retained name
            // field (name + kunya) and regenerated on EVERY admin write, not
            // only at import — import-only population makes an editor's
            // correction silently unfindable.
            $table->string('search_name_ar', 255)->nullable();
            $table->string('search_name_en', 255)->nullable();

            // Genealogy: descent, ordering, and render suppression.
            $table->unsignedBigInteger('father_id')->nullable();
            $table->unsignedBigInteger('mother_id')->nullable();
            $table->unsignedInteger('tree_display_order')->nullable();
            $table->boolean('hide_from_tree')->default(false);

            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Directory pagination and random selection.
            $table->index(['is_published', 'id']);
            // Descendant lookup — the two directions the tree walks downward.
            $table->index('father_id');
            $table->index('mother_id');
        });

        Schema::table('muslims', function (Blueprint $table): void {
            // RESTRICT, not cascade: deleting someone who is a parent must fail
            // loudly rather than silently orphaning a subtree.
            $table->foreign('father_id')->references('id')->on('muslims')->restrictOnDelete();
            $table->foreign('mother_id')->references('id')->on('muslims')->restrictOnDelete();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            // Blueprint cannot express a CHECK constraint.
            DB::statement(
                'ALTER TABLE muslims ADD CONSTRAINT chk_muslims_has_name '.
                'CHECK (name_ar IS NOT NULL OR name_en IS NOT NULL)'
            );

            // TWO separate single-column FULLTEXT indexes, never one composite.
            // MySQL requires a MATCH column list to exactly equal an index, and
            // search issues separate Arabic and English branches merged by
            // score. A composite index would make MATCH(search_name_ar) an
            // error rather than a slow query.
            DB::statement('ALTER TABLE muslims ADD FULLTEXT ft_muslims_search_name_ar (search_name_ar)');
            DB::statement('ALTER TABLE muslims ADD FULLTEXT ft_muslims_search_name_en (search_name_en)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('muslims');
    }
};
