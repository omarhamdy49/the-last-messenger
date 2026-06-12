<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

/**
 * Validates quick-filter chip updates — same contract as creation;
 * the unique key rule already ignores the route-bound chip id.
 */
class UpdateQuickFilterChipRequest extends StoreQuickFilterChipRequest {}
