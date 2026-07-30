<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One table for narrators AND graders. Both are the same entity — a scholar — and al-Bukhari is
     * both, so separate tables would duplicate his name, kunya and death year.
     */
    public function up(): void
    {
        Schema::create('hadith_scholars', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();

            $table->json('name');
            $table->json('full_name')->nullable();
            $table->json('kunya')->nullable();
            $table->json('biography')->nullable();

            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable();

            // tabaqa, e.g. "1st"
            $table->string('generation', 50)->nullable();
            $table->string('reliability', 100)->nullable();

            $table->boolean('is_companion')->default(false);

            // Cross-module FK by design. Deptrac does not inspect the database, so the rule is
            // enforced in code review: no PHP in Domain/Hadith may import Domain/Sahaba. Resolve
            // companions through a Shared contract instead.
            $table->foreignId('sahaba_id')->nullable()->unique()->constrained('sahaba')->nullOnDelete();

            $table->timestamps();

            $table->index('is_companion');
            $table->index('death_year_hijri');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_scholars');
    }
};
