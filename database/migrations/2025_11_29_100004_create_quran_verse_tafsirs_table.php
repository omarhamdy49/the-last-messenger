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
        Schema::create('quran_verse_tafsirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->string('verse_key', 20); // Format: "2:255"
            $table->string('lang', 5); // ar, en, fr, tr, ur
            $table->longText('text');
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('verse_id');
            $table->index('verse_key');
            $table->index('lang');
            $table->index('active');
            $table->unique(['verse_id', 'lang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_verse_tafsirs');
    }
};
