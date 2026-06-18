<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add publication status (draft/published) to Seerah events.
 *
 * Mirrors the Articles publish workflow: drafts are editable in the admin portal
 * but hidden from the public site/API. Existing events default to 'published' so
 * nothing currently live disappears.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->enum('status', ['draft', 'published'])
                    ->default('published')
                    ->after('importance');
                $table->index('status');
            });
        } else {
            // Non-MySQL drivers (tests): plain string column with the same default.
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->string('status', 20)->default('published')->after('importance');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
