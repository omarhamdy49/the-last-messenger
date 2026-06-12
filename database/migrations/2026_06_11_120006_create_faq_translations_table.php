<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale question/answer for FAQs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')
                ->constrained('faqs')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('question', 500)->nullable();
            $table->text('answer')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['faq_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_translations');
    }
};
