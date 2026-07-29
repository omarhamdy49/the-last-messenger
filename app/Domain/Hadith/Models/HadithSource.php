<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithSource extends Model
{
    protected $table = 'hadith_sources';

    protected $fillable = [
        'hadith_id',
        'source_system',
        'source_type',
        'external_id',
        'source_file',
        'title',
        'author',
        'reference',
        'url',
        'verification_method',
        'verified_at',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function scopeByReferenceCode($query, string $code)
    {
        return $query->where('reference', $code);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    public function getFullReferenceAttribute(): string
    {
        return implode(' ', array_filter([$this->title, $this->reference]));
    }
}
