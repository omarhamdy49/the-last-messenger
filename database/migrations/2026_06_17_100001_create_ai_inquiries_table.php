<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Ask Quran" AI inquiries — history, audit, cost ledger, cross-user answer
 * cache source, and the moderation surface (publish-as-FAQ / edit / delete).
 *
 * Portable schema only (no MySQL-only DDL) so the test driver runs it unguarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('locale', 5)->default('ar');

            // Question as typed + normalized form (diacritics stripped, lowercased,
            // whitespace collapsed) and its hash — the cross-user answer-cache key.
            $table->string('question', 255);
            $table->string('normalized_question', 255);
            $table->char('question_hash', 64);

            $table->longText('answer');
            $table->json('citations')->nullable();

            // 'llm' = a real DeepSeek call (consumed quota); 'cache' = served from a
            // prior answer (free, no quota). Kept as a constrained string for portability.
            $table->string('source', 10)->default('llm');
            $table->string('model')->nullable();

            // Cost ledger — populated from the DeepSeek usage object per call.
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cached_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->unsignedTinyInteger('tool_calls')->default(0);

            // Client geo snapshot (see AQ-18). Untrusted/advisory — analytics only,
            // never used for auth/quota/pricing.
            $table->json('geo')->nullable();

            // Moderation: set when an admin publishes this answer into the FAQ module.
            // Plain FK (no Eloquent relation) to keep the Quran model free of a
            // cross-module import into the Content domain (deptrac boundary).
            $table->foreignId('published_faq_id')->nullable()
                ->constrained('faqs')->nullOnDelete();

            // Admin answer-edit audit.
            $table->timestamp('answer_edited_at')->nullable();
            $table->foreignId('answer_edited_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // preserve cost/audit history on moderation delete

            $table->index(['user_id', 'created_at']); // user history + per-user audit
            $table->index(['question_hash', 'locale']); // answer-cache lookup
            $table->index('published_faq_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_inquiries');
    }
};
