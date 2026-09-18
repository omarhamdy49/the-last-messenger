<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — application credentials for the public API.
 *
 * Every /api/v1 route except health requires X-Api-Key + X-Api-Secret. The secret is
 * stored as an HMAC-SHA256 under APP_KEY and shown to the admin exactly once.
 * Clients carry scopes, an optional origin allow-list and their own rate limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('key', 40)->unique();
            $table->char('secret_hash', 64);
            $table->json('scopes');
            $table->json('allowed_origins')->nullable();
            $table->unsignedSmallInteger('rate_limit_per_minute')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
