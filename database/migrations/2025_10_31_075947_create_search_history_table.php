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
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('query', 500);
            $table->string('module')->nullable(); // article, hadith, media, etc.
            $table->json('filters')->nullable();
            $table->json('results_meta')->nullable(); // Store result count, time taken, etc.
            $table->unsignedInteger('results_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('searched_at');
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('module');
            $table->index('searched_at');
            $table->index(['user_id', 'searched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
