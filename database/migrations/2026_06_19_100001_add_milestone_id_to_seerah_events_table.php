<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link Seerah events to their milestone (الحقبة). Nullable + null-on-delete so a
 * milestone can be removed without cascading away its events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->foreignId('seerah_milestone_id')
                ->nullable()
                ->after('seerah_event_type_id')
                ->constrained('seerah_milestones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seerah_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seerah_milestone_id');
        });
    }
};
