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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->enum('type', ['blog', 'media', 'timeline'])->default('blog');
            $table->json('name'); // Multi-language support
            $table->json('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable(); // Hex color
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('type');
            $table->index('parent_id');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
