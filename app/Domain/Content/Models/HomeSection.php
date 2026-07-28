<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\HomeSectionKey;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class HomeSection extends Model
{
    use HasLocalizedContent;

    protected $table = 'home_sections';

    protected $fillable = [
        'key',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'key' => HomeSectionKey::class,
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function getTranslationModelClass(): string
    {
        return HomeSectionTranslation::class;
    }

    public function getTranslatableFields(): array
    {
        return ['heading', 'subheading'];
    }

    public function getTranslationForeignKey(): string
    {
        return 'home_section_id';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
