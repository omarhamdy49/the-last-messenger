<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Social login (Google/Facebook via Socialite) identity columns.
 *
 * Password note: the `password` column stays NOT NULL on purpose. Altering a
 * column on the live `users` table (DDL + table rebuild risk) is avoided by
 * giving social-only accounts a random hashed password at creation time
 * (see SocialAuthController). Those users sign in via the provider; the random
 * password is never communicated, so it cannot be used to log in by guessing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 20 chars is ample for provider keys ('google', 'facebook', ...).
            $table->string('provider', 20)->nullable()->after('avatar_path');
            // Provider subject ids are numeric/opaque strings well under 191.
            $table->string('provider_id', 191)->nullable()->after('provider');

            // One local account per (provider, provider id). Short explicit
            // name keeps it under MySQL's 64-char identifier limit.
            $table->unique(['provider', 'provider_id'], 'users_provider_pid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_provider_pid_unique');
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
