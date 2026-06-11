<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_programs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category', 30)->index();
            $table->string('maturity_rating', 20)->default('all');
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('rating', 3, 1)->default(0.0);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_trending')->default(false)->index();
            $table->unsignedInteger('views_count')->default(0)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'display_order']);
            $table->index(['is_featured', 'display_order']);
            $table->index(['is_trending', 'views_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_programs');
    }
};
