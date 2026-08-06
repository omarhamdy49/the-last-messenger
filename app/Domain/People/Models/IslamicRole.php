<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use Database\Factories\IslamicRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A capacity a historical figure held — sahabi, narrator, scholar, grader,
 * compiler. Seeded, not imported.
 *
 * @property int $id
 * @property string $slug
 * @property string $name_ar
 * @property string $name_en
 */
class IslamicRole extends Model
{
    use HasFactory;

    protected $table = 'islamic_roles';

    /** Explicit — `$guarded = []` is forbidden (constitution III). */
    protected $fillable = ['slug', 'name_ar', 'name_en'];

    public function muslims(): BelongsToMany
    {
        return $this->belongsToMany(Muslim::class, 'muslims_roles', 'role_id', 'muslim_id')
            ->withPivot(['is_featured', 'sort_order'])
            ->withTimestamps();
    }

    public function name(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? $this->name_ar : $this->name_en;
    }

    /** Domain models live outside app/Models, so the factory is named explicitly. */
    protected static function newFactory(): IslamicRoleFactory
    {
        return IslamicRoleFactory::new();
    }
}
