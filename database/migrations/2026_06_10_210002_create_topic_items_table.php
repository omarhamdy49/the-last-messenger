<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')
                ->constrained('topics')
                ->cascadeOnDelete();
            // Polymorphic link to a Verse / Hadith / Article (resolved via morph map).
            $table->string('itemable_type', 191);
            $table->unsignedBigInteger('itemable_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // A given content item appears at most once per topic.
            $table->unique(['topic_id', 'itemable_type', 'itemable_id'], 'topic_items_unique');
            // Hot path: list a topic's items of one type, ordered.
            $table->index(['topic_id', 'itemable_type', 'sort_order'], 'topic_items_lookup');
            $table->index(['itemable_type', 'itemable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_items');
    }
};
