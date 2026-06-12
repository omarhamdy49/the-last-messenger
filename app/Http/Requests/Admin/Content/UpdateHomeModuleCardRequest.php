<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

/**
 * Validates home module card updates — same contract as creation;
 * the unique key rule already ignores the route-bound card id.
 */
class UpdateHomeModuleCardRequest extends StoreHomeModuleCardRequest {}
