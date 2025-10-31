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
        Schema::create('search_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module'); // article, hadith, media, etc.
            $table->json('default_filters')->nullable();
            $table->string('default_sort', 50)->default('relevance');
            $table->string('default_order', 10)->default('desc');
            $table->unsignedInteger('default_per_page')->default(15);
            $table->boolean('show_facets')->default(true);
            $table->boolean('highlight_results')->default(true);
            $table->string('language', 5)->default('en');
            $table->timestamps();

            // Unique constraint - one preference per user per module
            $table->unique(['user_id', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_preferences');
    }
};
