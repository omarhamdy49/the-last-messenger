<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user monthly quota counter for the "Ask Quran" AI feature.
 *
 * One row per (user, calendar month). The unique key lets the repository do a
 * race-safe "reserve a slot" via an atomic conditional increment without
 * scanning the inquiries table. Only successful DeepSeek calls increment this;
 * cache hits don't, and failures refund.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_inquiry_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('period', 7); // 'YYYY-MM'
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_inquiry_usages');
    }
};
