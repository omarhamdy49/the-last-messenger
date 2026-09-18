<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

enum SyncRunMode: string
{
    case BOOTSTRAP = 'bootstrap';
    case INCREMENTAL = 'incremental';
    case SNAPSHOT = 'snapshot';
    case FETCH = 'fetch';
}
