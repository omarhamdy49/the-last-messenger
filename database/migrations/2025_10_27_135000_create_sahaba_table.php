<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sahaba', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // Names in multiple languages
            $table->json('name'); // {"ar": "أبو بكر الصديق", "en": "Abu Bakr As-Siddiq"}
            $table->json('full_name')->nullable(); // Complete name with lineage
            $table->json('kunya')->nullable(); // Abu/Umm name {"ar": "أبو بكر", "en": "Abu Bakr"}
            $table->json('laqab')->nullable(); // Honorific/title {"ar": "الصديق", "en": "As-Siddiq"}

            // Gender
            $table->enum('gender', ['male', 'female'])->default('male');

            // Status and rank
            $table->enum('status', [
                'prophet_companion', // Sahabi/Sahabiyyah
                'ten_promised_paradise', // Al-Asharah Al-Mubashsharah
                'badr_participant', // أهل بدر
                'uhud_participant',
                'early_muslim', // السابقون الأولون
                'muhajir', // Emigrated to Madinah
                'ansar', // Helpers from Madinah
                'tabi', // Follower (not companion but important)
            ])->default('prophet_companion');

            // Biographical information
            $table->json('biography')->nullable(); // Multi-language detailed biography
            $table->json('virtues')->nullable(); // Special virtues/qualities mentioned in hadith
            $table->json('famous_narrations')->nullable(); // Notable hadith narrated

            // Dates (Hijri and Gregorian)
            $table->string('birth_date_hijri')->nullable();
            $table->string('death_date_hijri')->nullable();
            $table->date('birth_date_gregorian')->nullable();
            $table->date('death_date_gregorian')->nullable();
            $table->integer('age_at_death')->nullable();

            // Places
            $table->string('birth_place')->nullable();
            $table->string('death_place')->nullable();
            $table->string('burial_place')->nullable();

            // Family relations (will link to people table)
            $table->unsignedBigInteger('father_id')->nullable(); // References people table
            $table->unsignedBigInteger('mother_id')->nullable();

            // Tribe information
            $table->string('tribe')->nullable(); // e.g., "Quraysh", "Aws", "Khazraj"
            $table->string('clan')->nullable(); // Sub-tribe

            // Conversion to Islam
            $table->string('conversion_date_hijri')->nullable();
            $table->integer('conversion_order')->nullable(); // e.g., 1st, 2nd, 3rd person to accept Islam

            // Role and contributions
            $table->json('roles')->nullable(); // ["Caliph", "Commander", "Scholar", "Narrator"]
            $table->integer('hadith_count')->default(0); // Number of hadith narrated

            // Battle participation
            $table->json('battles')->nullable(); // ["Badr", "Uhud", "Khandaq"]

            // Images and media
            $table->string('profile_image')->nullable();
            $table->json('gallery')->nullable();

            // Related content
            $table->foreignId('related_article_id')->nullable()->constrained('articles')->onDelete('set null');

            // Metadata
            $table->unsignedBigInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->integer('importance_rank')->default(0); // For sorting by importance

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('gender');
            $table->index('status');
            $table->index('tribe');
            $table->index('father_id');
            $table->index('mother_id');
            $table->index('is_featured');
            $table->index('importance_rank');
            $table->index('views_count');
            $table->index('conversion_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sahaba');
    }
};
