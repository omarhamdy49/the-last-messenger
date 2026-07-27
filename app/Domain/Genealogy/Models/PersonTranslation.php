<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Models;

use App\Domain\Sahaba\Models\SahabiTranslation;

/**
 * Compatibility alias. Genealogy translations are stored only in
 * `sahabi_translations`.
 */
class PersonTranslation extends SahabiTranslation
{
    protected $table = 'sahabi_translations';
}
