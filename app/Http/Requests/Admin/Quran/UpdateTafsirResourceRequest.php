<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

/**
 * Validates tafsir source updates — same contract as creation, with the slug
 * uniqueness check ignoring the source being edited.
 */
class UpdateTafsirResourceRequest extends StoreTafsirResourceRequest {}
