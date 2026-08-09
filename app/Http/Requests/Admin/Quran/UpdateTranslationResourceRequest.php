<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

/**
 * Validates translation edition updates — same contract as creation, with the
 * slug uniqueness check ignoring the edition being edited.
 */
class UpdateTranslationResourceRequest extends StoreTranslationResourceRequest {}
