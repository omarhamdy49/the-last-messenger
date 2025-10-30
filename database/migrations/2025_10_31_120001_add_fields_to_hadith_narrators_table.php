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
        Schema::table('hadith_narrators', function (Blueprint $table) {
            // Add JSON name column (if not exists)
            if (!Schema::hasColumn('hadith_narrators', 'name')) {
                $table->json('name')->nullable()->after('slug');
            }

            // Add JSON biography column (if not exists)
            if (!Schema::hasColumn('hadith_narrators', 'biography_json')) {
                $table->json('biography_json')->nullable()->after('name');
            }

            // Add generated columns for full-text search (if not exists)
            if (!Schema::hasColumn('hadith_narrators', 'name_ar_generated')) {
                $table->string('name_ar_generated')->nullable()->storedAs("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))");
            }
            if (!Schema::hasColumn('hadith_narrators', 'name_en_generated')) {
                $table->string('name_en_generated')->nullable()->storedAs("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))");
            }

            // Add new fields (if not exists)
            if (!Schema::hasColumn('hadith_narrators', 'era')) {
                $table->enum('era', [
                    'Sahabah',
                    'Tabiun',
                    'Taba_al_Tabiun',
                    'Taba_Taba_al_Tabiun',
                    'Later_Scholars'
                ])->nullable()->after('reliability_grade');
            }

            if (!Schema::hasColumn('hadith_narrators', 'country')) {
                $table->string('country', 100)->nullable()->after('era');
            }
            if (!Schema::hasColumn('hadith_narrators', 'birth_hijri')) {
                $table->string('birth_hijri', 20)->nullable()->after('country');
            }
            if (!Schema::hasColumn('hadith_narrators', 'death_hijri')) {
                $table->string('death_hijri', 20)->nullable()->after('birth_hijri');
            }
            if (!Schema::hasColumn('hadith_narrators', 'teachers')) {
                $table->json('teachers')->nullable()->after('death_hijri');
            }
            if (!Schema::hasColumn('hadith_narrators', 'students')) {
                $table->json('students')->nullable()->after('teachers');
            }
            if (!Schema::hasColumn('hadith_narrators', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('students');
            }

            // Add soft deletes (if not exists)
            if (!Schema::hasColumn('hadith_narrators', 'deleted_at')) {
                $table->softDeletes();
            }

            // Add indexes
            $table->index('name_ar_generated');
            $table->index('name_en_generated');
            $table->index('era');
            $table->index('country');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith_narrators', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['name_ar_generated']);
            $table->dropIndex(['name_en_generated']);
            $table->dropIndex(['era']);
            $table->dropIndex(['country']);
            $table->dropIndex(['status']);

            // Drop new columns
            $table->dropColumn([
                'name',
                'biography_json',
                'name_ar_generated',
                'name_en_generated',
                'era',
                'country',
                'birth_hijri',
                'death_hijri',
                'teachers',
                'students',
                'status',
                'deleted_at',
            ]);
        });
    }
};
