<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §7 — narrators and graders in one table.
 *
 * One table because both roles describe the same entity: al-Bukhari is both a
 * narrator and a grader, and two tables would duplicate his name, patronymic
 * and death year.
 *
 * `sahaba_id` is a DATABASE foreign key only. No PHP in Domain\Hadith may
 * import Domain\Sahaba; companion resolution goes through a Shared contract.
 *
 * No index on `is_companion` (low cardinality) or `death_year_hijri` — both
 * would be speculative until a query needs them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_scholars', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->json('name');
            $table->json('full_name')->nullable();
            $table->json('kunya')->nullable();
            $table->json('biography')->nullable();
            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable();
            // tabaqa
            $table->string('generation', 50)->nullable();
            $table->string('reliability', 100)->nullable();
            $table->boolean('is_companion')->default(false);
            $table->timestamps();
        });

        // Added separately so the cross-module link is an explicit, isolated
        // statement rather than something buried in the table definition.
        Schema::table('hadith_scholars', function (Blueprint $table): void {
            $table->foreignId('sahaba_id')->nullable()->unique()
                ->constrained('sahaba')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_scholars');
    }
};
