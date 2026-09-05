<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Models\AdminLog;
use App\Domain\Shared\Services\DatabaseDumpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Download and delete database archives from the settings hub.
 *
 * Creating one is NOT here — that goes through the existing allowlisted-command
 * runner (`db:dump` in config/artisan-commands.php), which already gives a queued
 * run, progress polling and a CommandRun audit row. This controller only serves
 * what that produced.
 *
 * Every route is super_admin-only, deliberately stricter than the rest of the
 * settings hub: an archive carries every password hash, Sanctum token and email
 * address in the system, so it is a full account-takeover payload. Editors and
 * ordinary admins can reach Settings; none of them can reach these.
 */
class DatabaseBackupController extends Controller
{
    public function __construct(private readonly DatabaseDumpService $dumps) {}

    public function download(Request $request, string $filename): BinaryFileResponse
    {
        $path = $this->dumps->resolve($filename);

        abort_if($path === null, 404);

        // Logged before the response is sent: exporting the whole database is the
        // single most sensitive action in the panel and must leave a trail even if
        // the transfer is later aborted.
        AdminLog::create([
            'user_id' => $request->user('admin')?->id,
            'action' => 'database.backup.downloaded',
            'model_type' => 'database_backup',
            'model_id' => null,
            'new_values' => ['file' => basename($path), 'size' => filesize($path) ?: 0],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()
            ->download($path, basename($path), [
                'Content-Type' => 'application/gzip',
                // A dump must never sit in a shared cache.
                'Cache-Control' => 'no-store, private',
            ]);
    }

    public function destroy(Request $request, string $filename): RedirectResponse
    {
        $path = $this->dumps->resolve($filename);

        abort_if($path === null, 404);

        $name = basename($path);
        $deleted = $this->dumps->delete($filename);

        if ($deleted) {
            AdminLog::create([
                'user_id' => $request->user('admin')?->id,
                'action' => 'database.backup.deleted',
                'model_type' => 'database_backup',
                'model_id' => null,
                'new_values' => ['file' => $name],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]);
        }

        return redirect()
            ->route('admin.settings')
            ->with($deleted ? 'success' : 'error', $deleted
                ? "تم حذف النسخة {$name}."
                : "تعذّر حذف النسخة {$name}.");
    }
}
