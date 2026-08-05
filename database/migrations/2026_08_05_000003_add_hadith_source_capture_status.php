<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_collections', function (Blueprint $table): void {
            // NULL means the source did not publish a capture verdict. False is
            // materially different: readers must be warned that rows are absent.
            $table->boolean('source_capture_complete')->nullable()->after('source_category');
        });

        Schema::table('hadith_books', function (Blueprint $table): void {
            $table->unsignedSmallInteger('source_status')->nullable()->after('source_url');
            $table->string('source_error', 500)->nullable()->after('source_status');
        });
    }

    public function down(): void
    {
        Schema::table('hadith_books', function (Blueprint $table): void {
            $table->dropColumn(['source_status', 'source_error']);
        });

        Schema::table('hadith_collections', function (Blueprint $table): void {
            $table->dropColumn('source_capture_complete');
        });
    }
};
