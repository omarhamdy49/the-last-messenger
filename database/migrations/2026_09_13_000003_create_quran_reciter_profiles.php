<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_reciter_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('identity_key')->unique();
            $table->string('slug')->unique();
            $table->string('country')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedBigInteger('default_recording_id')->nullable();
            $table->timestamps();
        });
        Schema::create('quran_reciter_profile_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('profile_id')->constrained('quran_reciter_profiles')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['profile_id', 'locale']);
        });
        Schema::table('quran_reciters', function (Blueprint $table): void {
            $table->foreignId('profile_id')->nullable()->constrained('quran_reciter_profiles')->restrictOnDelete();
        });
        Schema::create('quran_reciter_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('recording_id')->constrained('quran_reciters')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_reciter_aliases');
        Schema::table('quran_reciters', fn (Blueprint $table) => $table->dropConstrainedForeignId('profile_id'));
        Schema::dropIfExists('quran_reciter_profile_translations');
        Schema::dropIfExists('quran_reciter_profiles');
    }
};
