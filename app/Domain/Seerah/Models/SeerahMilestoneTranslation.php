<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeerahMilestoneTranslation Model
 *
 * Per-locale story title / subtitle / description for SeerahMilestone.
 *
 * @property int $id
 * @property int $sm_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $description
 * @property bool $is_ready
 */
class SeerahMilestoneTranslation extends Model
{
    /**
     * @var string
     */
    protected $table = 'seerah_milestone_translations';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sm_id',
        'locale',
        'title',
        'subtitle',
        'description',
        'is_ready',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sm_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent milestone.
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(SeerahMilestone::class, 'sm_id');
    }
}
