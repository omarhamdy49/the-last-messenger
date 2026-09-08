<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Artisan Commands exposed in the admin settings panel
|--------------------------------------------------------------------------
|
| ONLY the command names listed here can be executed over HTTP. Anything not
| in this file is rejected before it reaches the Artisan kernel — the admin UI
| is never a generic shell.
|
| Arguments and options are deliberately NOT described here: they are read
| from the Artisan registry at runtime (InputDefinition), so a command's own
| signature stays the single source of truth and the form never drifts.
|
| Per-command keys (all optional):
|   queued    run on the queue with progress polling instead of inline.
|             Anything that talks to an external service MUST be queued —
|             an inline run dies on PHP's max_execution_time.
|   danger    the UI asks for a typed confirmation first.
|   defaults  input forced on every run, e.g. --force so a command guarded by
|             ConfirmableTrait does not block on a console prompt no one can
|             answer.
|
*/

return [

    // How often (ms) the progress modal polls a queued run.
    'poll_interval' => 2000,

    // A command missing from the registry (package removed, typo) is skipped
    // silently rather than rendering a button that 500s on click.
    'groups' => [

        'quran' => [
            'icon' => 'book-open',
            'commands' => [
                'quran:import' => ['queued' => true, 'danger' => true],
                'quran:sync' => ['queued' => true],
                'quran:sync-arabic' => ['queued' => true],
                'quran:import-editions' => ['queued' => true],
                'quran:import-translation' => ['queued' => true],
                'quran:import-tafsir' => ['queued' => true],
                'quran:import-reciters' => ['queued' => true],
                'quran:import-recitations' => ['queued' => true],
                // Replaces the whole-surah audio of every mapped reciter so it
                // matches the timings Phase 2 highlights from — hence danger.
                // --force because --fresh is guarded by ConfirmableTrait and
                // nothing here can answer a console prompt.
                'quran:sync-mp3quran' => [
                    'queued' => true,
                    'danger' => true,
                    'defaults' => ['--force' => true],
                ],
                'quran:import-mushaf-layout' => ['queued' => true],
                'quran:enrich-reciters' => ['queued' => true],
                'quran:backfill-hizb' => ['queued' => true],
                // Manual-only (no longer scheduled). Queued because a first run
                // after a long gap deletes in batches until the table is clear.
                'quran:ai-prune' => ['queued' => true],
            ],
        ],

        'hadith' => [
            'icon' => 'scroll-text',
            'commands' => [
                // Streams the Sunnah.com API: minutes, not seconds.
                'hadith:import' => ['queued' => true, 'defaults' => ['--force' => true]],
            ],
        ],

        'people' => [
            'icon' => 'users',
            'commands' => [
                'muslims:import' => ['queued' => true],
                'users:backfill-public-role' => [],
            ],
        ],

        'media' => [
            'icon' => 'clapperboard',
            'commands' => [
                'media:compute-trending' => [],
                'media:publish-scheduled' => [],
                // Manual-only (no longer scheduled). Queued: the delete loop
                // runs until the backlog is clear, which can be many batches.
                'media:prune-view-events' => ['queued' => true],
            ],
        ],

        'content' => [
            'icon' => 'file-text',
            'commands' => [
                'articles:publish-scheduled' => [],
                'portal:verify' => ['queued' => true],
                'portal:export' => ['queued' => true],
            ],
        ],

        'cache' => [
            'icon' => 'zap',

            // Every command that DROPS the config cache is queued on purpose.
            //
            // ArtisanCommandService::start() runs non-queued commands inline, in the web
            // request — as www-data. php-fpm may not be able to read shared/.env directly
            // (mode 640 deployer:www-data at best, and a hand-edit silently resets it to
            // 600), in which case bootstrap/cache/config.php is the ONLY configuration
            // source it has. Clearing it from inside a web request therefore removed
            // APP_KEY and the DB credentials from every subsequent request and took the
            // whole site down until someone SSHed in to re-run config:cache.
            //
            // Queue workers run as ${DEPLOY_USER} (see supervisor-worker.conf), which can
            // read .env, so the same command is safe there — and the CommandRun row gives
            // the operator progress instead of a hung request.
            'commands' => [
                'optimize' => ['queued' => true],
                'optimize:clear' => ['queued' => true],
                'cache:clear' => ['queued' => true],
                'config:cache' => ['queued' => true],
                'config:clear' => ['queued' => true],
                'route:cache' => ['queued' => true],
                'route:clear' => ['queued' => true],
                'view:cache' => ['queued' => true],
                'view:clear' => ['queued' => true],
                'event:clear' => ['queued' => true],
            ],
        ],

        'system' => [
            'icon' => 'server-cog',
            'commands' => [
                'about' => [],
                'storage:link' => [],
                // Queued without exception: the production database is ~470 MB and
                // an inline run dies on max_execution_time part-way through writing
                // the archive, leaving a truncated file that looks like a backup.
                'db:dump' => ['queued' => true],
                // Runs nightly on its own; exposed here for an on-demand prune.
                'admin-logs:prune' => ['queued' => true],
                'migrate:status' => [],
                'migrate' => ['danger' => true, 'defaults' => ['--force' => true]],
                'schedule:list' => [],
                'queue:failed' => [],
                'queue:retry' => [],
                'queue:prune-failed' => [],
                'queue:restart' => [],
            ],
        ],
    ],
];
