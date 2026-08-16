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
            'commands' => [
                'optimize' => [],
                'optimize:clear' => [],
                'cache:clear' => [],
                'config:cache' => [],
                'config:clear' => [],
                'route:cache' => [],
                'route:clear' => [],
                'view:cache' => [],
                'view:clear' => [],
                'event:clear' => [],
            ],
        ],

        'system' => [
            'icon' => 'server-cog',
            'commands' => [
                'about' => [],
                'storage:link' => [],
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
