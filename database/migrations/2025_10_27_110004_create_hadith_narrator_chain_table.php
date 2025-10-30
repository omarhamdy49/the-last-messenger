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
        Schema::create('hadith_narrator_chain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith')->onDelete('cascade');
            $table->foreignId('narrator_id')->constrained('hadith_narrators')->onDelete('cascade');
            $table->integer('position'); // Order in the chain (1 = first narrator, 2 = second, etc.)
            $table->timestamps();

            // Indexes
            $table->index('hadith_id');
            $table->index('narrator_id');
            $table->index(['hadith_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_narrator_chain');
    }
};
