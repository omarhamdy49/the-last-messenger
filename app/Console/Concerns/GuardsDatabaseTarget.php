<?php

declare(strict_types=1);

namespace App\Console\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fail-closed guard for commands that write or destroy data.
 *
 * A mis-targeted `migrate:fresh` destroyed the dev database during this
 * rebuild. Resolving the database name from the live connection — not from
 * config or an env var — means a stale `--env` or a silent `.env` fallback
 * cannot route a destructive command at the wrong schema.
 */
trait GuardsDatabaseTarget
{
    /**
     * @param  list<string>  $allowlist  database names this command may touch
     *
     * @throws RuntimeException when the resolved database is not allowlisted
     */
    protected function assertSafeDatabase(array $allowlist): void
    {
        $database = (string) DB::connection()->getDatabaseName();

        if ($allowlist === []) {
            throw new RuntimeException('Database allowlist is empty; refusing to run.');
        }

        if (! in_array($database, $allowlist, true)) {
            throw new RuntimeException(sprintf(
                'Refusing to run against database [%s]. Allowed: %s.',
                $database === '' ? '(none)' : $database,
                implode(', ', $allowlist),
            ));
        }
    }
}
