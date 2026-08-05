<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->enum('display_grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])
                ->nullable()
                ->default(null)
                ->change();
        });

        DB::table('hadith_hadiths')
            ->where('display_grade', 'unknown')
            ->update(['display_grade' => null]);

        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->enum('display_grade', ['sahih', 'hasan', 'daif', 'mawdu'])
                ->nullable()
                ->default(null)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->enum('display_grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])
                ->nullable()
                ->default(null)
                ->change();
        });

        DB::table('hadith_hadiths')
            ->whereNull('display_grade')
            ->update(['display_grade' => 'unknown']);

        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->enum('display_grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])
                ->default('unknown')
                ->nullable(false)
                ->change();
        });
    }
};
