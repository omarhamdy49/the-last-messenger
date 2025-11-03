<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Seerah-specific (Islamic biography) fields to articles table for comprehensive
     * historical event tracking with dates, locations, and audience targeting.
     */
    public function up(): void
    {
        if (!Schema::hasTable('articles')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            // Date fields for historical events
            if (!Schema::hasColumn('articles', 'hijri_date')) {
                $table->string('hijri_date')->nullable()->after('published_at')
                    ->comment('Hijri date in human-readable format (e.g., "20 Ramadan 8 AH")');
            }

            if (!Schema::hasColumn('articles', 'gregorian_date')) {
                $table->date('gregorian_date')->nullable()->after('hijri_date')
                    ->comment('Gregorian equivalent date for historical events');
            }

            if (!Schema::hasColumn('articles', 'event_start_date')) {
                $table->date('event_start_date')->nullable()->after('gregorian_date')
                    ->comment('Start date for multi-day events');
            }

            if (!Schema::hasColumn('articles', 'event_end_date')) {
                $table->date('event_end_date')->nullable()->after('event_start_date')
                    ->comment('End date for multi-day events');
            }

            // Location fields for geographical mapping
            if (!Schema::hasColumn('articles', 'event_locations')) {
                $table->json('event_locations')->nullable()->after('event_end_date')
                    ->comment('Array of location objects: [{name: "", lat: "", lng: ""}]');
            }

            if (!Schema::hasColumn('articles', 'primary_location')) {
                $table->string('primary_location')->nullable()->after('event_locations')
                    ->comment('Main location name for the event');
            }

            // Educational targeting fields
            if (!Schema::hasColumn('articles', 'difficulty_level')) {
                $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])
                    ->nullable()->after('primary_location')
                    ->comment('Content difficulty level for educational purposes');
            }

            if (!Schema::hasColumn('articles', 'target_audience')) {
                $table->enum('target_audience', ['children', 'youth', 'adults', 'scholars'])
                    ->nullable()->after('difficulty_level')
                    ->comment('Primary target audience for the content');
            }

            // Add indexes for common queries
            $table->index('gregorian_date');
            $table->index('event_start_date');
            $table->index('event_end_date');
            $table->index('primary_location');
            $table->index('difficulty_level');
            $table->index('target_audience');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Drop Seerah-specific columns if they exist
            $columns = [
                'hijri_date',
                'gregorian_date',
                'event_start_date',
                'event_end_date',
                'event_locations',
                'primary_location',
                'difficulty_level',
                'target_audience',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
