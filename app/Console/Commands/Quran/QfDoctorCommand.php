<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use App\Domain\Quran\Import\QuranFoundation\TokenProvider;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Checks the Quran Foundation credentials and which capabilities they unlock,
 * without printing any secret. Content must be OK before any import runs.
 */
final class QfDoctorCommand extends Command
{
    protected $signature = 'quran:qf:doctor';

    protected $description = 'Check Quran Foundation API credentials and available capabilities (token, content, hadith, reflect)';

    public function handle(TokenProvider $tokens, Endpoints $qf): int
    {
        $this->line('Environment: '.config('quran.qf.env').' · API: '.config('quran.qf.api_url'));

        $rows = [];
        try {
            $tokens->forget();
            $tokens->token();
            $rows[] = ['token', 'OK'];
        } catch (QfApiException $e) {
            $rows[] = ['token', $e->type];
            $this->table(['capability', 'status'], $rows);

            return self::FAILURE;
        } catch (RuntimeException $e) {
            $rows[] = ['token', $e->getMessage()];
            $this->table(['capability', 'status'], $rows);

            return self::FAILURE;
        }

        $content = $this->probe(fn () => count($qf->chapters('en')['chapters'] ?? []) === 114 ? 'OK' : 'unexpected chapter count');
        $rows[] = ['content', $content];
        $rows[] = ['hadith', $this->probe(fn () => $qf->hadithReferences('12:12', 'en') !== [] ? 'OK' : 'empty')];
        $rows[] = ['reflect', $this->probe(fn () => isset($qf->reflectFeed(['limit' => 1])['total']) ? 'OK' : 'unexpected payload')];

        $this->table(['capability', 'status'], $rows);

        return $content === 'OK' ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  callable(): string  $call
     */
    private function probe(callable $call): string
    {
        try {
            return $call();
        } catch (QfApiException $e) {
            return $e->type;
        } catch (QfPayloadException) {
            return 'unexpected payload';
        }
    }
}
