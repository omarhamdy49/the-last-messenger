<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale content for static pages. `body` holds sanitized HTML
 * produced by the admin editor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('static_page_id')
                ->constrained('static_pages')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title', 255)->nullable();
            $table->longText('body')->nullable();
            $table->string('seo_title', 255)->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['static_page_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_page_translations');
    }
};
