<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

/**
 * Validates menu item updates — same contract as creation; the parent
 * rule already excludes the route-bound item id (no self-parenting).
 */
class UpdateMenuItemRequest extends StoreMenuItemRequest {}
