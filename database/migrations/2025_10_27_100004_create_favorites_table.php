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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('favorable'); // Polymorphic relation (automatically creates index)
            $table->timestamps();

            // Unique constraint for user-item combination
            $table->unique(['user_id', 'favorable_id', 'favorable_type'], 'favorites_unique');
        });

        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('interactable'); // Polymorphic relation
            $table->enum('interaction_type', ['view', 'progress', 'complete'])->default('view');
            $table->json('metadata')->nullable(); // Store progress data, last position, etc.
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'interactable_type', 'interactable_id'], 'user_interactions_idx');
            $table->index('interaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_interactions');
        Schema::dropIfExists('favorites');
    }
};
