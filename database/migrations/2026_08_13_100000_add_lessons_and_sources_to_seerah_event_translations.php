<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الدروس والعبر + المصادر والمراجع for Seerah events.
 *
 * Both sections exist in the UI mock-up (seerah-event-single-article-enhanced.html)
 * but had no column, no admin field and no public markup — the event page shipped
 * 3 of the mock-up's 5 sections, and its scroll-spy tracked a `sources` anchor that
 * never rendered.
 *
 * Stored per locale next to the other translated event copy, as JSON arrays:
 *   lessons: [{title, description}]
 *   sources: [{title, author, reference}]
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seerah_event_translations', function (Blueprint $table) {
            $table->json('lessons')->nullable()->after('content');
            $table->json('sources')->nullable()->after('lessons');
        });
    }

    public function down(): void
    {
        Schema::table('seerah_event_translations', function (Blueprint $table) {
            $table->dropColumn(['lessons', 'sources']);
        });
    }
};
