<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\CommandRun;
use App\Jobs\RunArtisanCommandJob;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Throwable;

/**
 * Runs whitelisted Artisan commands on behalf of the admin settings panel.
 *
 * Two guarantees hold everything together:
 *
 *  1. Only names present in config/artisan-commands.php are ever executed, and
 *     only inputs declared by the command's own signature are ever forwarded.
 *     This is a curated launcher, never a shell.
 *
 *  2. Anything marked `queued` runs in a worker and reports through a
 *     CommandRun row, so an import that streams an external API for twenty
 *     minutes is not bound to a PHP request's max_execution_time.
 */
class ArtisanCommandService
{
    /**
     * Never runnable over HTTP, whatever config/artisan-commands.php says.
     *
     * Each of these drops or rewinds the schema. The whitelist alone is one
     * careless line away from putting a "wipe production" button in the admin
     * panel — and from letting a test that posts the name execute it for real.
     */
    private const FORBIDDEN = [
        'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback',
        'db:wipe', 'schema:dump',
    ];

    /** Global console options that are noise in a web form. */
    private const HIDDEN_OPTIONS = [
        'help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi',
        'no-interaction', 'env', 'silent', 'profile',
    ];

    /**
     * Whitelisted commands that actually exist in the registry, grouped for
     * the settings tab.
     *
     * @return array<int, array{key:string, icon:string, commands:array<int, array<string, mixed>>}>
     */
    public function catalog(): array
    {
        $registry = $this->registry();
        $groups = [];

        foreach ((array) config('artisan-commands.groups', []) as $key => $group) {
            $commands = [];

            foreach ((array) ($group['commands'] ?? []) as $name => $settings) {
                // A command removed from the app (or from a dropped package)
                // is skipped rather than rendering a button that 500s.
                if (! isset($registry[$name]) || in_array($name, self::FORBIDDEN, true)) {
                    continue;
                }

                $commands[] = $this->describe($name, (array) $settings, $registry[$name]);
            }

            if ($commands !== []) {
                $groups[] = [
                    'key' => (string) $key,
                    'icon' => (string) ($group['icon'] ?? 'terminal'),
                    'commands' => $commands,
                ];
            }
        }

        return $groups;
    }

    /**
     * Metadata for one whitelisted command, or null when it is not allowed.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $name): ?array
    {
        // Checked before the whitelist: this is the one gate both the
        // controller and execute() share, so it is where "impossible" belongs.
        if (in_array($name, self::FORBIDDEN, true)) {
            return null;
        }

        foreach ((array) config('artisan-commands.groups', []) as $group) {
            $settings = ($group['commands'] ?? [])[$name] ?? null;

            if ($settings === null) {
                continue;
            }

            $command = $this->registry()[$name] ?? null;

            return $command === null ? null : $this->describe($name, (array) $settings, $command);
        }

        return null;
    }

    /**
     * Start a run. Queued commands return immediately with a row to poll;
     * inline commands come back already finished.
     *
     * @param  array<string, mixed>  $meta  from find()
     * @param  array<string, mixed>  $posted  raw form input
     */
    public function start(array $meta, array $posted, ?int $userId): CommandRun
    {
        $input = $this->buildInput($meta, $posted);

        $run = CommandRun::create([
            'command' => $meta['name'],
            'input' => $input,
            'status' => CommandRun::STATUS_QUEUED,
            'is_queued' => (bool) $meta['queued'],
            'created_by' => $userId,
        ]);

        if ($meta['queued']) {
            RunArtisanCommandJob::dispatch($run->id);

            return $run;
        }

        $this->execute($run);

        return $run->refresh();
    }

    /**
     * Execute the run and stream console output into its row. Called inline by
     * start() for quick commands and by the queue worker for long ones.
     */
    public function execute(CommandRun $run): void
    {
        $meta = $this->find($run->command);

        if ($meta === null) {
            // The whitelist changed between queueing and running.
            $run->forceFill([
                'status' => CommandRun::STATUS_FAILED,
                'error' => 'Command is no longer allowed: '.$run->command,
                'finished_at' => now(),
            ])->save();

            return;
        }

        $run->forceFill([
            'status' => CommandRun::STATUS_RUNNING,
            'output' => null,
            'error' => null,
            'progress_current' => 0,
            'progress_total' => 0,
            'started_at' => $run->started_at ?? now(),
        ])->save();

        $output = $this->outputWriter($run);

        try {
            // --no-interaction is forced on every run: nothing can answer a
            // console prompt from here, and an unanswered one hangs the worker
            // until the job times out.
            $parameters = array_merge((array) $run->input, ['--no-interaction' => true]);

            $exitCode = Artisan::call($meta['name'], $parameters, $output);

            $output->flush();

            $run->forceFill([
                'status' => $exitCode === SymfonyCommand::SUCCESS
                    ? CommandRun::STATUS_COMPLETED
                    : CommandRun::STATUS_FAILED,
                'exit_code' => $exitCode,
                'error' => $exitCode === SymfonyCommand::SUCCESS ? null : 'Exit code '.$exitCode,
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $output->flush();

            report($e);

            $run->forceFill([
                'status' => CommandRun::STATUS_FAILED,
                'exit_code' => SymfonyCommand::FAILURE,
                'error' => mb_substr($e->getMessage(), 0, 60000),
                'finished_at' => now(),
            ])->save();
        }
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Console output that lands in the ledger instead of a terminal.
     *
     * Writes are buffered in memory and flushed at most once a second: a
     * progress bar emits a line per item, and a DB write per item would cost
     * more than the import itself.
     *
     * Return type is left off deliberately: callers need the anonymous class's
     * flush(), which Output does not declare.
     */
    private function outputWriter(CommandRun $run)
    {
        return new class($run) extends Output
        {
            private string $buffer = '';

            private float $lastFlush = 0.0;

            private int $current = 0;

            private int $total = 0;

            private string $message = '';

            public function __construct(private readonly CommandRun $run)
            {
                // Undecorated: ANSI colour codes are noise in an HTML <pre>.
                parent::__construct(self::VERBOSITY_NORMAL, false);
            }

            protected function doWrite(string $message, bool $newline): void
            {
                // A progress bar redraws with \r; treat each redraw as a line
                // so the stored log is readable rather than one giant line.
                $clean = str_replace("\r", "\n", $this->strip($message));

                $this->buffer .= $clean.($newline ? PHP_EOL : '');
                $this->scan($clean);

                if (microtime(true) - $this->lastFlush >= 1.0) {
                    $this->flush();
                }
            }

            /** Persist whatever has accumulated. Safe to call repeatedly. */
            public function flush(): void
            {
                $this->lastFlush = microtime(true);

                if (mb_strlen($this->buffer) > CommandRun::OUTPUT_LIMIT) {
                    // Keep the tail: the end of a failed import is the part
                    // that explains the failure.
                    $this->buffer = '…'.mb_substr($this->buffer, -CommandRun::OUTPUT_LIMIT);
                }

                $this->run->forceFill([
                    'output' => $this->buffer,
                    'progress_current' => $this->current,
                    'progress_total' => $this->total,
                    'message' => $this->message !== '' ? mb_substr($this->message, 0, 500) : $this->run->message,
                ])->save();
            }

            private function strip(string $value): string
            {
                return (string) preg_replace('/\e\[[0-9;?]*[A-Za-z]/', '', $value);
            }

            /**
             * Derive progress from whatever the command already prints. Any
             * "12/114" — Symfony's ProgressBar, or a hand-rolled "[3/9] step"
             * — drives the bar, so commands need no changes to report.
             */
            private function scan(string $chunk): void
            {
                foreach (preg_split('/\R/', $chunk) ?: [] as $line) {
                    $line = trim($line);

                    if ($line === '') {
                        continue;
                    }

                    $this->message = $line;

                    if (preg_match_all('/(\d+)\s*\/\s*(\d+)/', $line, $m, PREG_SET_ORDER)) {
                        $last = end($m);
                        $total = (int) $last[2];

                        if ($total > 0) {
                            $this->current = (int) $last[1];
                            $this->total = $total;
                        }
                    }
                }
            }
        };
    }

    /**
     * Map posted form values onto Artisan parameters, accepting only names the
     * command's own signature declares.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $posted
     * @return array<string, mixed>
     */
    private function buildInput(array $meta, array $posted): array
    {
        $parameters = [];

        foreach ($meta['arguments'] as $argument) {
            $value = $posted['arguments'][$argument['name']] ?? null;

            if ($this->blank($value)) {
                continue;
            }

            $parameters[$argument['name']] = $argument['is_array']
                ? $this->toList($value)
                : (string) $value;
        }

        foreach ($meta['options'] as $option) {
            $value = $posted['options'][$option['name']] ?? null;

            if (! $option['accepts_value']) {
                // Flags are present-or-absent; a false checkbox must not send
                // "--dry-run=0", which Symfony reads as the flag being set.
                if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                    $parameters['--'.$option['name']] = true;
                }

                continue;
            }

            if ($this->blank($value)) {
                continue;
            }

            $parameters['--'.$option['name']] = $option['is_array']
                ? $this->toList($value)
                : (string) $value;
        }

        // Forced input wins: it is what keeps ConfirmableTrait commands from
        // blocking on a prompt.
        return array_merge($parameters, $meta['defaults']);
    }

    private function blank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /** @return array<int, string> */
    private function toList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), fn (string $v): bool => $v !== ''));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn (string $v): bool => $v !== ''));
    }

    /**
     * Describe a command from its own signature — never from a hand-written
     * copy, which would drift the moment a signature changes.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function describe(string $name, array $settings, SymfonyCommand $command): array
    {
        $definition = $command->getNativeDefinition();

        $arguments = [];

        foreach ($definition->getArguments() as $argument) {
            /** @var InputArgument $argument */
            $arguments[] = [
                'name' => $argument->getName(),
                'description' => (string) $argument->getDescription(),
                'required' => $argument->isRequired(),
                'is_array' => $argument->isArray(),
                'default' => $this->scalarDefault($argument->getDefault()),
            ];
        }

        $options = [];

        foreach ($definition->getOptions() as $option) {
            /** @var InputOption $option */
            if (in_array($option->getName(), self::HIDDEN_OPTIONS, true)) {
                continue;
            }

            // A forced default is not the admin's to change.
            if (array_key_exists('--'.$option->getName(), (array) ($settings['defaults'] ?? []))) {
                continue;
            }

            $options[] = [
                'name' => $option->getName(),
                'description' => (string) $option->getDescription(),
                'accepts_value' => $option->acceptValue(),
                'is_array' => $option->isArray(),
                'default' => $this->scalarDefault($option->getDefault()),
            ];
        }

        return [
            'name' => $name,
            'description' => (string) $command->getDescription(),
            'queued' => (bool) ($settings['queued'] ?? false),
            'danger' => (bool) ($settings['danger'] ?? false),
            'defaults' => (array) ($settings['defaults'] ?? []),
            'arguments' => $arguments,
            'options' => $options,
        ];
    }

    private function scalarDefault(mixed $default): string
    {
        if ($default === null || is_bool($default)) {
            return '';
        }

        return is_array($default) ? implode(',', $default) : (string) $default;
    }

    /**
     * The Artisan kernel already memoizes this after the first bootstrap, so
     * there is nothing to cache here.
     *
     * @return array<string, SymfonyCommand>
     */
    private function registry(): array
    {
        try {
            return Artisan::all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}
