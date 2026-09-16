<?php

declare(strict_types=1);

use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Services\LanguageService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(LanguageService::class)->seedDefaultLanguages();
        Language::query()->where('code', 'ar')->update(['is_active' => true]);
        Language::clearCache();
        if (! Language::query()->where('is_default', true)->exists()
            || Language::query()->whereIn('code', ['fr', 'ur'])->where('is_default', true)->exists()) {
            app(LanguageService::class)->setDefaultLanguage('ar');
        }
        Language::query()->whereIn('code', ['fr', 'ur'])->update(['is_active' => false]);
        Language::clearCache();
    }

    public function down(): void
    {
        // Language preferences and stored translations survive a code rollback.
    }
};
