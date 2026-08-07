<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_chapters', function (Blueprint $table): void {
            $table->text('title_ar')->change();
            $table->text('title_en')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hadith_chapters', function (Blueprint $table): void {
            $table->string('title_ar', 500)->change();
            $table->string('title_en', 500)->nullable()->change();
        });
    }
};
