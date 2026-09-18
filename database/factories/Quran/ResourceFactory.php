<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\ResourceSource;
use App\Domain\Quran\Models\QuranResource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuranResource>
 */
class ResourceFactory extends Factory
{
    protected $model = QuranResource::class;

    public function definition(): array
    {
        $name = 'Resource '.Str::random(6);

        return [
            'kind' => ResourceKind::TRANSLATION,
            'qf_id' => null,
            'source' => ResourceSource::MANUAL,
            'slug' => Str::slug($name),
            'name' => $name,
            'author_name' => 'Author',
            'language_iso' => 'en',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 0,
            'sync_enabled' => false,
        ];
    }

    public function translation(string $languageIso = 'en'): static
    {
        return $this->state(fn (): array => ['kind' => ResourceKind::TRANSLATION, 'language_iso' => $languageIso]);
    }

    public function tafsir(string $languageIso = 'ar'): static
    {
        return $this->state(fn (): array => ['kind' => ResourceKind::TAFSIR, 'language_iso' => $languageIso]);
    }

    public function chapterInfo(string $languageIso = 'en'): static
    {
        return $this->state(fn (): array => ['kind' => ResourceKind::CHAPTER_INFO, 'language_iso' => $languageIso]);
    }

    public function recitation(): static
    {
        return $this->state(fn (): array => [
            'kind' => ResourceKind::RECITATION,
            'language_iso' => null,
            'style' => RecitationStyle::MURATTAL,
        ]);
    }

    public function chapterReciter(): static
    {
        return $this->state(fn (): array => [
            'kind' => ResourceKind::CHAPTER_RECITER,
            'language_iso' => null,
            'style' => RecitationStyle::MURATTAL,
            'qirat' => 'Hafs',
        ]);
    }

    public function wordTranslation(string $languageIso = 'en'): static
    {
        return $this->state(fn (): array => ['kind' => ResourceKind::WORD_TRANSLATION, 'language_iso' => $languageIso]);
    }

    public function wordTransliteration(string $languageIso = 'en'): static
    {
        return $this->state(fn (): array => ['kind' => ResourceKind::WORD_TRANSLITERATION, 'language_iso' => $languageIso]);
    }

    public function qf(int $qfId): static
    {
        return $this->state(fn (): array => ['source' => ResourceSource::QF, 'qf_id' => $qfId, 'qf_content_id' => $qfId]);
    }

    public function manual(): static
    {
        return $this->state(fn (): array => ['source' => ResourceSource::MANUAL, 'qf_id' => null]);
    }

    public function externallySourced(ExternalSource $source, string $refId): static
    {
        return $this->state(fn (): array => ['external_source' => $source, 'external_ref_id' => $refId]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function asDefault(): static
    {
        return $this->state(fn (): array => ['is_default' => true, 'is_active' => true]);
    }
}
