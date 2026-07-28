<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HomeSectionTranslation extends Model
{
    protected $table = 'home_section_translations';

    protected $fillable = [
        'home_section_id',
        'locale',
        'heading',
        'subheading',
        'is_ready',
    ];

    protected $casts = [
        'home_section_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(HomeSection::class, 'home_section_id');
    }
}
