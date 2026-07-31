<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->foreignId('primary_narrator_id')->nullable()
                ->after('chapter_id')
                ->constrained('hadith_scholars')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('primary_narrator_id');
        });
    }
};
