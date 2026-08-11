<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Application\DTOs\AiAnswerDTO;
use App\Domain\Quran\Infrastructure\Llm\OpenAiCompatChatClient;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;
use App\Domain\Quran\Services\QuranAiInquiryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Side-by-side provider comparison for the Ask Quran AI bridge — the POC that
 * decides which LLM answers the feature in production.
 *
 * Every provider runs through the SAME QuranAiInquiryService: same system prompt
 * (concise and --detailed variants alike), same quran.ai MCP grounding, same
 * bounded tool loop. Only the chat client and the pricing table differ, so the
 * measured difference is the model's.
 *
 * Usage: php artisan quran:ai-compare "ما معنى الكوثر؟" --locale=ar --detailed --save
 */
final class AskQuranCompareCommand extends Command
{
    protected $signature = 'quran:ai-compare
        {question : The question to ask both providers}
        {--locale=ar : Answer locale (ar|en|fr|tr|ur)}
        {--detailed : Use the thorough (advanced) prompt variant}
        {--providers=deepseek,gemini : Comma-separated services.* provider keys}
        {--save : Write a Markdown report to storage/app/quran-ai-compare/}';

    protected $description = 'Run one question through two LLM providers with identical prompts and compare answer, grounding, cost and latency.';

    public function handle(): int
    {
        $question = (string) $this->argument('question');
        $locale = (string) $this->option('locale');
        $detailed = (bool) $this->option('detailed');

        $providers = array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) $this->option('providers'))
        )));

        if (count($providers) < 2) {
            $this->error('Give at least two providers, e.g. --providers=deepseek,gemini');

            return self::FAILURE;
        }

        foreach ($providers as $provider) {
            if (empty(config("services.{$provider}.api_key"))) {
                $this->error(strtoupper($provider).'_API_KEY is not set — cannot compare.');

                return self::FAILURE;
            }
        }

        $this->info("Q ({$locale}".($detailed ? ', detailed' : ', concise')."): {$question}");
        $this->newLine();

        /** @var array<string, array<string, mixed>> $runs */
        $runs = [];
        foreach ($providers as $provider) {
            $runs[$provider] = $this->runProvider($provider, $question, $locale, $detailed);
        }

        $this->renderComparison($runs);

        foreach ($runs as $provider => $run) {
            $this->newLine();
            $this->line("<fg=cyan>───── {$provider} ({$run['model']}) ─────</>");
            $this->line($run['error'] ?? $run['answer']);
        }

        if ($this->option('save')) {
            $path = $this->saveReport($question, $locale, $detailed, $runs);
            $this->newLine();
            $this->info("Report: {$path}");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function runProvider(string $provider, string $question, string $locale, bool $detailed): array
    {
        $this->comment("→ {$provider} …");

        // A fresh MCP client per run: it carries per-request session state, and
        // each provider must get its own un-warmed grounding session.
        $service = new QuranAiInquiryService(
            new OpenAiCompatChatClient($provider),
            app(QuranMcpClientInterface::class),
            $provider,
        );

        $startedAt = microtime(true);

        try {
            $answer = $service->answer($question, $locale, $detailed);
        } catch (\Throwable $e) {
            return [
                'model' => (string) config("services.{$provider}.model", $provider),
                // The domain error is deliberately generic; the provider status
                // and body are in the log (a free-tier 429 looks like any other).
                'error' => 'FAILED: '.$e->getMessage().' — see storage/logs/laravel.log for the provider status',
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
        }

        return $this->metrics($answer, (int) round((microtime(true) - $startedAt) * 1000));
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(AiAnswerDTO $answer, int $latencyMs): array
    {
        // Grounding discipline: every referenced verse must carry an inline
        // [[surah:ayah]] marker, and a bare "(سورة ١٠٨)" style reference is the
        // failure mode the system prompt forbids.
        preg_match_all('/\[\[\d+:\d+(?:-\d+)?\]\]/u', $answer->answer, $markers);

        return [
            'model' => $answer->model,
            'answer' => $answer->answer,
            'error' => null,
            'latency_ms' => $latencyMs,
            'tool_calls' => $answer->toolCalls,
            'markers' => count($markers[0]),
            'chars' => mb_strlen($answer->answer),
            'input' => $answer->inputTokens,
            'cached' => $answer->cachedTokens,
            'output' => $answer->outputTokens,
            'cost_usd' => $answer->costUsd,
            'citations' => $answer->citations,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $runs
     */
    private function renderComparison(array $runs): void
    {
        $metrics = [
            'model' => 'model',
            'latency_ms' => 'latency (ms)',
            'tool_calls' => 'MCP tool calls',
            'markers' => 'verse markers',
            'chars' => 'answer chars',
            'input' => 'input tokens',
            'cached' => 'cached tokens',
            'output' => 'output tokens (incl. thinking)',
            'cost_usd' => 'cost USD',
        ];

        $rows = [];
        foreach ($metrics as $key => $label) {
            $row = [$label];
            foreach ($runs as $run) {
                $value = $run[$key] ?? '—';
                $row[] = $key === 'cost_usd' && is_float($value)
                    ? '$'.number_format($value, 6)
                    : (string) $value;
            }
            $rows[] = $row;
        }

        $this->newLine();
        $this->table(array_merge(['metric'], array_keys($runs)), $rows);

        $costs = array_filter(array_map(static fn (array $r): ?float => $r['cost_usd'] ?? null, $runs));
        if (count($costs) === count($runs) && min($costs) > 0) {
            $this->line(sprintf('  cost ratio (max/min): %.1fx', max($costs) / min($costs)));
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $runs
     */
    private function saveReport(string $question, string $locale, bool $detailed, array $runs): string
    {
        $lines = [
            '# Ask Quran — provider comparison',
            '',
            '- **Question:** '.$question,
            '- **Locale:** '.$locale.' · **Prompt variant:** '.($detailed ? 'detailed' : 'concise'),
            '- **Run at:** '.now()->toDateTimeString(),
            '',
        ];

        foreach ($runs as $provider => $run) {
            $lines[] = '## '.$provider.' — `'.($run['model'] ?? '?').'`';
            $lines[] = '';

            if (($run['error'] ?? null) !== null) {
                $lines[] = '> '.$run['error'];
                $lines[] = '';

                continue;
            }

            $lines[] = sprintf(
                '`%d ms` · `%d tool calls` · `%d verse markers` · in %d (cached %d) / out %d · **$%s**',
                $run['latency_ms'], $run['tool_calls'], $run['markers'],
                $run['input'], $run['cached'], $run['output'], number_format((float) $run['cost_usd'], 6),
            );
            $lines[] = '';

            foreach ((array) $run['citations'] as $citation) {
                $lines[] = '- `'.$citation['tool'].'` '.json_encode($citation['args'] ?? [], JSON_UNESCAPED_UNICODE);
            }

            $lines[] = '';
            $lines[] = '### Answer';
            $lines[] = '';
            $lines[] = (string) $run['answer'];
            $lines[] = '';
        }

        $path = 'quran-ai-compare/'.now()->format('Y-m-d_His').'_'.$locale.($detailed ? '_detailed' : '').'.md';
        Storage::disk('local')->put($path, implode("\n", $lines));

        return Storage::disk('local')->path($path);
    }
}
