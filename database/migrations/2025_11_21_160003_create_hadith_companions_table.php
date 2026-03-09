<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_companions', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable()->comment('Companion name in Arabic');
            $table->string('name_en')->nullable()->comment('Companion name in English');
            $table->text('full_name_ar')->nullable()->comment('Full name in Arabic');
            $table->text('full_name_en')->nullable()->comment('Full name in English');
            $table->string('title_ar')->nullable()->comment('Title in Arabic (e.g., أمير المؤمنين)');
            $table->string('title_en')->nullable()->comment('Title in English (e.g., Amir al-Mu\'minin)');
            $table->integer('birth_year_hijri')->nullable()->comment('Birth year (Hijri calendar)');
            $table->integer('death_year_hijri')->nullable()->comment('Death year (Hijri calendar)');
            $table->string('conversion_year')->nullable()->comment('Year of conversion to Islam');
            $table->text('bio_ar')->nullable()->comment('Biography in Arabic');
            $table->text('bio_en')->nullable()->comment('Biography in English');
            $table->text('virtues')->nullable()->comment('Virtues and merits');
            $table->boolean('verified')->default(false)->comment('Data verification status');
            $table->timestamps();

            // Indexes
            $table->index('name_ar');
            $table->index('name_en');
            $table->index('verified');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_companions ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }

        // Add FULLTEXT index
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE hadith_companions ADD FULLTEXT INDEX ft_companions_names (name_ar, name_en, title_ar, title_en)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_companions');
    }
};
