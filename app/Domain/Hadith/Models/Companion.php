<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Companion extends Model
{
    protected $table = 'hadith_companions';

    protected $fillable = [
        'name_ar', 'name_en', 'full_name_ar', 'full_name_en',
        'title_ar', 'title_en', 'birth_year_hijri', 'death_year_hijri',
        'conversion_year', 'bio_ar', 'bio_en', 'virtues', 'verified',
    ];

    protected $casts = [
        'birth_year_hijri' => 'integer',
        'death_year_hijri' => 'integer',
        'verified' => 'boolean',
    ];

    public function hadiths(): HasMany
    {
        return $this->hasMany(Hadith::class, 'companion_id');
    }

    public static function findOrCreateByName(array $data): self
    {
        if (!empty($data['name_ar'])) {
            $companion = static::where('name_ar', $data['name_ar'])->first();
            if ($companion) return $companion;
        }

        if (!empty($data['name_en'])) {
            $companion = static::where('name_en', $data['name_en'])->first();
            if ($companion) return $companion;
        }

        return static::create($data);
    }
}
