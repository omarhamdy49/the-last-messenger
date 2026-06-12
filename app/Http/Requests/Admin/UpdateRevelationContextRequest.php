<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

/**
 * Update Revelation Context Request
 * Same constraints as creation — the full record is replaced on update.
 */
class UpdateRevelationContextRequest extends StoreRevelationContextRequest {}
