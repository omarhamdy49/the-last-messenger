<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * Narrators and graders in one table, because both roles describe the same
 * entity: al-Bukhari is both, and two tables would duplicate his name,
 * patronymic and death year.
 *
 * `sahaba_id` is a DATABASE foreign key only. This class must never import
 * anything from Domain\Sahaba — deptrac enforces it, and companion resolution
 * goes through a Shared contract. There is deliberately no `sahabi()` relation.
 *
 * @property int $id
 * @property string|null $external_id
 * @property string $slug
 * @property int|null $birth_year_hijri
 * @property int|null $death_year_hijri
 * @property string|null $generation
 * @property string|null $reliability
 * @property bool $is_companion
 * @property int|null $sahaba_id
 * @property string|null $source_url
 */
class HadithScholar extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'hadith_scholars';

    /** @var array<string> */
    public array $translatable = ['name', 'full_name', 'kunya', 'biography'];

    /** @var array<string> */
    protected $fillable = [
        'external_id',
        'slug',
        'name',
        'full_name',
        'kunya',
        'biography',
        'birth_year_hijri',
        'death_year_hijri',
        'generation',
        'reliability',
        'is_companion',
        'sahaba_id',
        'source_url',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'birth_year_hijri' => 'integer',
            'death_year_hijri' => 'integer',
            'is_companion' => 'boolean',
            'sahaba_id' => 'integer',
        ];
    }

    public function gradings(): HasMany
    {
        return $this->hasMany(HadithGrading::class, 'scholar_id');
    }

    public function primaryHadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'primary_narrator_id');
    }

    public function narratedHadiths(): BelongsToMany
    {
        return $this->belongsToMany(Hadith::class, 'hadith_narrators', 'scholar_id', 'hadith_id')
            ->withPivot(['position', 'display_name_ar', 'source_metadata'])
            ->withTimestamps();
    }

    /**
     * Factories live under Database\Factories\Hadith, which Laravel cannot
     * derive from this model's namespace.
     */
    protected static function newFactory(): \Database\Factories\Hadith\HadithScholarFactory
    {
        return \Database\Factories\Hadith\HadithScholarFactory::new();
    }
}
