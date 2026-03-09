<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // person_translations table
        Schema::create('person_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->longText('biography')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('death_place')->nullable();
            $table->string('burial_place')->nullable();
            $table->string('occupation')->nullable();
            $table->longText('notes')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['person_id', 'locale'], 'person_trans_uniq');
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'title', 'biography']);
            }
        });

        // reciter_translations table
        Schema::create('reciter_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reciter_id')->constrained('quran_reciters')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['reciter_id', 'locale'], 'reciter_trans_uniq');
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });

        // seerah_participant_translations table
        Schema::create('seerah_participant_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seerah_participant_id')->constrained('seerah_participants')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['seerah_participant_id', 'locale'], 'seerah_part_trans_uniq');
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });

        // language_translations table
        Schema::create('language_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['language_id', 'locale'], 'language_trans_uniq');
            $table->index('locale');
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['name']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_translations');
        Schema::dropIfExists('seerah_participant_translations');
        Schema::dropIfExists('reciter_translations');
        Schema::dropIfExists('person_translations');
    }
};
