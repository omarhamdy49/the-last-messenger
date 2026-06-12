<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

/**
 * Validates static page updates — same contract as creation; the unique
 * slug rule already ignores the route-bound page id.
 */
class UpdateStaticPageRequest extends StoreStaticPageRequest {}
