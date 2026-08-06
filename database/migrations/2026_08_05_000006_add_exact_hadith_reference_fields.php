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
            $table->string('source_in_book_reference', 255)->nullable()->after('source_reference');
            $table->string('source_translation_reference', 255)->nullable()->after('source_in_book_reference');
        });
    }

    public function down(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->dropColumn(['source_in_book_reference', 'source_translation_reference']);
        });
    }
};
