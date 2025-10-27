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
        Schema::create('seerah_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title'); // Multi-language
            $table->json('description'); // Multi-language
            $table->json('content')->nullable(); // Detailed multi-language content
            
            // Date fields (Hijri and Gregorian)
            $table->string('hijri_date')->nullable(); // e.g., "1 Muharram 1 AH"
            $table->integer('hijri_year')->nullable();
            $table->integer('hijri_month')->nullable();
            $table->integer('hijri_day')->nullable();
            $table->date('gregorian_date')->nullable();
            $table->integer('gregorian_year')->nullable();
            
            // Event categorization
            $table->enum('event_type', [
                'birth',
                'death',
                'battle',
                'migration',
                'revelation',
                'treaty',
                'marriage',
                'miracle',
                'other'
            ])->default('other');
            
            $table->enum('importance', ['high', 'medium', 'low'])->default('medium');
            
            // Location (optional)
            $table->string('location')->nullable(); // e.g., "Makkah", "Madinah"
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Media
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable(); // Array of image URLs
            
            // Relationships
            $table->foreignId('related_article_id')->nullable()->constrained('articles')->onDelete('set null');
            
            // Metadata
            $table->unsignedBigInteger('views_count')->default(0);
            $table->integer('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('event_type');
            $table->index('importance');
            $table->index('hijri_year');
            $table->index('hijri_month');
            $table->index('gregorian_year');
            $table->index('gregorian_date');
            $table->index('display_order');
            $table->index('is_featured');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seerah_events');
    }
};

