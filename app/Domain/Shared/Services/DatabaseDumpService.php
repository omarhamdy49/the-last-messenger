<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Produces gzipped mysqldump archives of the application database.
 *
 * Three things here are load-bearing and must not be "simplified" away:
 *
 * 1. The password NEVER reaches argv. `mysqldump -pSECRET` is visible to every
 *    user on the box through `ps aux` / /proc, and this runs on a shared VPS.
 *    It goes in a 0600 defaults-extra-file that is deleted in a finally block.
 * 2. No shell. The process is built from an argument array, so a database name
 *    or password containing shell metacharacters cannot break out.
 * 3. The dump is streamed straight into a gzip handle. The production database
 *    is ~470 MB uncompressed; buffering that in PHP would exhaust memory_limit.
 *
 * Dumps live under storage/app/private — never a public disk. A dump contains
 * every password hash, Sanctum token and email address in the system.
 */
final class DatabaseDumpService
{
    /** Kept archives; older ones are pruned after each successful dump. */
    private const KEEP = 5;

    private const DIRECTORY = 'db-dumps';

    /** Guards both the download route and the prune walk against traversal. */
    public const FILENAME_PATTERN = '/^tlm-[a-z0-9_-]+-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz$/';

    public function directory(): string
    {
        $path = storage_path('app/private/'.self::DIRECTORY);

        if (! is_dir($path)) {
            mkdir($path, 0750, true);
        }

        return $path;
    }

    /**
     * Run the dump. Returns the absolute path of the archive written.
     *
     * @param  callable(string):void|null  $onProgress  receives human-readable status lines
     */
    public function dump(?callable $onProgress = null): string
    {
        $connection = (string) Config::get('database.default');
        $config = (array) Config::get("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException("Database dumps support MySQL only; the '{$connection}' connection is '".($config['driver'] ?? 'unknown')."'.");
        }

        $binary = $this->locateMysqldump();
        $filename = $this->filename();
        $target = $this->directory().'/'.$filename;
        $credentials = $this->writeCredentialsFile($config);

        $onProgress && $onProgress("Dumping {$config['database']} → {$filename}");

        try {
            $process = new Process([
                $binary,
                // MUST be first: mysqldump only honours it before other options.
                '--defaults-extra-file='.$credentials,
                '--single-transaction',   // consistent snapshot without locking the live site
                '--quick',                // row-at-a-time, do not buffer whole tables
                '--no-tablespaces',       // avoids needing the PROCESS privilege
                '--routines',
                '--default-character-set=utf8mb4',
                $config['database'],
            ]);

            // A large dump legitimately runs for minutes.
            $process->setTimeout(null);
            $process->setIdleTimeout(300);

            $handle = gzopen($target, 'wb6');

            if ($handle === false) {
                throw new RuntimeException("Could not open {$target} for writing.");
            }

            $stderr = '';

            try {
                $process->start();

                foreach ($process as $type => $chunk) {
                    if ($type === Process::OUT) {
                        gzwrite($handle, $chunk);

                        continue;
                    }

                    $stderr .= $chunk;
                }
            } finally {
                gzclose($handle);
            }

            if (! $process->isSuccessful()) {
                @unlink($target);

                throw new ProcessFailedException($process);
            }

            // mysqldump can exit 0 having written nothing useful (wrong grants,
            // empty schema). A truncated archive that looks fine until someone
            // tries to restore it is the worst possible outcome for a backup.
            $this->assertArchiveIsComplete($target);

            $onProgress && $onProgress('Wrote '.$this->humanSize(filesize($target) ?: 0));

            $pruned = $this->prune();

            if ($pruned > 0 && $onProgress) {
                $onProgress("Pruned {$pruned} older dump(s), keeping ".self::KEEP.'.');
            }

            if ($stderr !== '' && $onProgress) {
                $onProgress('mysqldump notices: '.trim($stderr));
            }

            return $target;
        } finally {
            @unlink($credentials);
        }
    }

    /**
     * Archives newest first.
     *
     * @return array<int, array{name: string, path: string, size: int, created_at: CarbonImmutable}>
     */
    public function archives(): array
    {
        $files = glob($this->directory().'/*.sql.gz') ?: [];

        $archives = [];

        foreach ($files as $path) {
            $name = basename($path);

            // Anything not matching the pattern was not written by us; ignore it
            // rather than offer it for download.
            if (preg_match(self::FILENAME_PATTERN, $name) !== 1) {
                continue;
            }

            $archives[] = [
                'name' => $name,
                'path' => $path,
                'size' => filesize($path) ?: 0,
                // createFromTimestamp() defaults to UTC, which rendered an archive
                // written at 17:00 as 14:00 next to timestamps elsewhere in the panel
                // that are already in app time.
                'created_at' => CarbonImmutable::createFromTimestamp(
                    filemtime($path) ?: 0,
                    Config::get('app.timezone', 'UTC'),
                ),
            ];
        }

        usort($archives, fn (array $a, array $b): int => $b['created_at'] <=> $a['created_at']);

        return $archives;
    }

    /**
     * Resolve a user-supplied filename to a real archive, or null.
     *
     * Both guards are needed: the pattern rejects `../` outright, and the realpath
     * check catches a symlink inside the directory pointing somewhere else.
     */
    public function resolve(string $filename): ?string
    {
        if (preg_match(self::FILENAME_PATTERN, $filename) !== 1) {
            return null;
        }

        $path = realpath($this->directory().'/'.$filename);
        $root = realpath($this->directory());

        if ($path === false || $root === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($path) ? $path : null;
    }

    public function delete(string $filename): bool
    {
        $path = $this->resolve($filename);

        return $path !== null && unlink($path);
    }

    /** Total bytes held by archives, for the disk-usage line in the UI. */
    public function totalSize(): int
    {
        return array_sum(array_column($this->archives(), 'size'));
    }

    public function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }

    /** @return int number of archives removed */
    private function prune(): int
    {
        $archives = $this->archives();

        if (count($archives) <= self::KEEP) {
            return 0;
        }

        $removed = 0;

        foreach (array_slice($archives, self::KEEP) as $archive) {
            if (@unlink($archive['path'])) {
                $removed++;
            }
        }

        return $removed;
    }

    private function filename(): string
    {
        $environment = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) app()->environment())) ?: 'app';

        return sprintf('tlm-%s-%s.sql.gz', $environment, CarbonImmutable::now()->format('Y-m-d-His'));
    }

    /**
     * A gzip archive whose last bytes are not a dump footer is truncated.
     */
    private function assertArchiveIsComplete(string $path): void
    {
        $handle = gzopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('The dump could not be reopened for verification.');
        }

        $tail = '';

        try {
            // Walk to the end keeping only the last chunk; the archive is far too
            // large to hold in memory.
            while (! gzeof($handle)) {
                $chunk = gzread($handle, 32768);

                if ($chunk === false) {
                    break;
                }

                $tail = substr($chunk, -256);
            }
        } finally {
            gzclose($handle);
        }

        if (! str_contains($tail, 'Dump completed')) {
            @unlink($path);

            throw new RuntimeException('The dump was incomplete and has been discarded.');
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function writeCredentialsFile(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tlmdump');

        if ($path === false) {
            throw new RuntimeException('Could not create a temporary credentials file.');
        }

        // Before writing: the file must never be world-readable, even briefly.
        chmod($path, 0600);

        $ini = "[client]\n"
            .'user="'.$this->escapeIni((string) ($config['username'] ?? '')).'"'."\n"
            .'password="'.$this->escapeIni((string) ($config['password'] ?? '')).'"'."\n"
            .'host="'.$this->escapeIni((string) ($config['host'] ?? '127.0.0.1')).'"'."\n"
            .'port='.(int) ($config['port'] ?? 3306)."\n";

        file_put_contents($path, $ini);

        return $path;
    }

    /** MySQL option files treat \ and " specially inside a quoted value. */
    private function escapeIni(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function locateMysqldump(): string
    {
        $configured = Config::get('database.dump_binary');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/opt/homebrew/bin/mysqldump'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        // Fall back to PATH resolution by Symfony's executable finder.
        $finder = new ExecutableFinder;
        $found = $finder->find('mysqldump');

        if ($found === null) {
            throw new RuntimeException('mysqldump was not found on this server. Install the MySQL client tools, or set database.dump_binary.');
        }

        return $found;
    }
}
