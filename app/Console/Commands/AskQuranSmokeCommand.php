<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Services\QuranAiInquiryService;
use Illuminate\Console\Command;

/**
 * Live smoke test for the Ask Quran AI bridge (DeepSeek V4 Flash + quran.ai MCP).
 *
 * This is the manual verification step the two infrastructure clients need —
 * it exercises the real MCP handshake/tool calls and the real DeepSeek wire
 * format, which unit tests (with fakes) cannot cover.
 *
 * Usage: php artisan quran:ai-smoke "What does the Quran say about patience?" --locale=en
 */
final class AskQuranSmokeCommand extends Command
{
    protected $signature = 'quran:ai-smoke {question : The question to ask} {--locale=en : Answer locale (ar|en|fr|tr|ur)} {--stream : Exercise the streaming (SSE) path} {--detailed : Request the thorough (2-slot) answer variant}';

    protected $description = 'Live smoke test of the Ask Quran AI bridge (DeepSeek + quran.ai MCP). Requires DEEPSEEK_API_KEY.';

    public function handle(QuranAiInquiryService $service): int
    {
        if (empty(config('services.deepseek.api_key'))) {
            $this->error('DEEPSEEK_API_KEY is not set — cannot run the live smoke test.');

            return self::FAILURE;
        }

        $question = (string) $this->argument('question');
        $locale = (string) $this->option('locale');
        $detailed = (bool) $this->option('detailed');

        $this->info("Asking ({$locale}".($detailed ? ', detailed' : '')."): {$question}");
        $this->newLine();

        try {
            if ($this->option('stream')) {
                $this->info('[streaming]');
                $answer = $service->streamAnswer($question, $locale, function (array $event): void {
                    if (($event['type'] ?? '') === 'delta') {
                        $this->output->write((string) ($event['text'] ?? ''));
                    } elseif (($event['type'] ?? '') === 'status') {
                        $this->output->write("\n  · {$event['tool']}".(isset($event['ref']) ? " ({$event['ref']})" : '')."\n");
                    }
                }, $detailed);
                $this->newLine(2);
            } else {
                $answer = $service->answer($question, $locale, $detailed);
            }
        } catch (\Throwable $e) {
            $this->error('Bridge failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($answer->answer === '') {
            $this->warn('The model returned an empty answer (check MCP connectivity / grounding).');

            return self::FAILURE;
        }

        $this->line($answer->answer);
        $this->newLine();

        $this->table(['metric', 'value'], [
            ['tool_calls', (string) $answer->toolCalls],
            ['input_tokens', (string) $answer->inputTokens],
            ['cached_tokens', (string) $answer->cachedTokens],
            ['output_tokens', (string) $answer->outputTokens],
            ['cost_usd', '$'.number_format($answer->costUsd, 6)],
            ['model', $answer->model],
        ]);

        foreach ($answer->citations as $citation) {
            $this->line('  • '.$citation['tool'].' '.json_encode($citation['args'] ?? [], JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
