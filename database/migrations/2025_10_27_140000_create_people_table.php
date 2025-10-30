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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name'); // Multi-language: {"ar": "محمد", "en": "Muhammad"}
            $table->json('full_name')->nullable(); // Complete name with lineage
            $table->json('title')->nullable(); // {"ar": "النبي", "en": "The Prophet"}
            $table->json('kunya')->nullable(); // Abu/Umm name
            $table->json('laqab')->nullable(); // Honorific title

            // Person type
            $table->enum('person_type', [
                'prophet',
                'companion_male',
                'companion_female',
                'family',
                'tribe_leader',
                'historical_figure',
            ])->default('historical_figure');

            // Biographical information
            $table->json('biography')->nullable(); // Multi-language biography
            $table->string('birth_place')->nullable();
            $table->string('death_place')->nullable();
            $table->string('birth_date_hijri')->nullable();
            $table->string('death_date_hijri')->nullable();
            $table->date('birth_date_gregorian')->nullable();
            $table->date('death_date_gregorian')->nullable();

            // Gender
            $table->enum('gender', ['male', 'female'])->default('male');

            // Images and media
            $table->string('profile_image')->nullable();
            $table->json('gallery')->nullable();

            // Link to Sahaba if this person is a companion
            $table->foreignId('sahaba_id')->nullable()->constrained('sahaba')->onDelete('set null');

            // Relations (denormalized for quick access)
            $table->unsignedBigInteger('father_id')->nullable();
            $table->unsignedBigInteger('mother_id')->nullable();

            // Metadata
            $table->unsignedBigInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->integer('generation')->nullable(); // Generation number from Prophet Adam

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('person_type');
            $table->index('gender');
            $table->index('father_id');
            $table->index('mother_id');
            $table->index('is_featured');
            $table->index('generation');

            // Foreign keys
            $table->foreign('father_id')->references('id')->on('people')->onDelete('set null');
            $table->foreign('mother_id')->references('id')->on('people')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
