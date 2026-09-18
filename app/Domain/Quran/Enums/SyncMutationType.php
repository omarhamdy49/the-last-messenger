<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * QF Content Sync mutation types and how each is applied locally
 * (api-docs.quran.foundation … /resources-sync).
 */
enum SyncMutationType: string
{
    /** Fetch snapshot_url and replace all local rows of the resource. */
    case RESOURCE_CREATE = 'RESOURCE_CREATE';

    /** Same as create: the resource changed too much for row deltas. */
    case RESOURCE_INVALIDATE = 'RESOURCE_INVALIDATE';

    /** Resource is no longer public: hide it locally. */
    case RESOURCE_DELETE = 'RESOURCE_DELETE';

    /** Freshness marker only; keep local rows. */
    case RESOURCE_UPDATE = 'RESOURCE_UPDATE';

    /** Upsert one row by record_type + record_key. */
    case ROW_CREATE = 'ROW_CREATE';

    case ROW_UPDATE = 'ROW_UPDATE';

    /** Delete one row by record_type + record_key. */
    case ROW_DELETE = 'ROW_DELETE';

    public function needsSnapshot(): bool
    {
        return $this === self::RESOURCE_CREATE || $this === self::RESOURCE_INVALIDATE;
    }
}
