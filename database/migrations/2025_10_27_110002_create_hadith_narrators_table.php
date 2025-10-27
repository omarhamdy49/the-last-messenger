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
        Schema::create('hadith_narrators', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_arabic');
            $table->string('name_english')->nullable();
            $table->text('biography')->nullable();
            $table->enum('reliability_grade', ['thiqah', 'saduq', 'daif', 'matruk', 'unknown'])->default('unknown');
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('name_arabic');
            $table->index('reliability_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_narrators');
    }
};

