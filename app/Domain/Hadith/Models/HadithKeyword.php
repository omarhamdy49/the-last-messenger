<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithKeyword extends Model
{
    protected $table = 'hadith_keywords';

    public $timestamps = false;

    protected $fillable = [
        'hadith_id',
        'locale',
        'keyword',
        'review_status',
        'source_reference',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where('keyword', 'like', "%{$keyword}%");
    }

    public function scopeUnique($query, string $locale = 'ar')
    {
        return $query->select('keyword', \DB::raw('COUNT(*) as total_frequency'))
            ->where('locale', $locale)
            ->groupBy('keyword')
            ->orderByDesc('total_frequency');
    }

    public function getKeyword(string $locale = 'ar'): ?string
    {
        return $this->locale === $locale ? $this->keyword : null;
    }

    public static function getTopKeywords(int $limit = 50, string $locale = 'ar')
    {
        return static::unique($locale)->limit($limit)->get();
    }
}
