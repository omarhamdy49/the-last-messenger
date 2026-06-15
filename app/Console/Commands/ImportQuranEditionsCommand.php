<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\TranslationResource;
use Illuminate\Console\Command;

/**
 * Orchestrates importing every translation and tafsir edition in the catalog
 * (config/quran-editions.php) from the Quran.com API. Idempotent — each
 * underlying import is keyed by (verse, resource), so re-running updates in place.
 *
 * Examples:
 *   php artisan quran:import-editions                    # all translations + tafsirs
 *   php artisan quran:import-editions --translations     # translations only
 *   php artisan quran:import-editions --tafsirs          # tafsirs only
 *   php artisan quran:import-editions --only=en-pickthall,ar-tabari
 */
class ImportQuranEditionsCommand extends Command
{
    protected $signature = 'quran:import-editions
        {--translations : Import translation editions only}
        {--tafsirs : Import tafsir editions only}
        {--only= : Comma-separated list of edition slugs to import}';

    protected $description = 'Import all Quran translation & tafsir editions from the catalog (config/quran-editions.php)';

    public function handle(): int
    {
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));
        $doTranslations = ! $this->option('tafsirs');
        $doTafsirs = ! $this->option('translations');

        $failures = 0;
        $ran = 0;

        if ($doTranslations) {
            $this->info('=== Translation editions ===');
            foreach (config('quran-editions.translations', []) as $entry) {
                $slug = (string) $entry['slug'];
                if ($only && ! in_array($slug, $only, true)) {
                    continue;
                }

                $this->line("→ {$slug}");
                $code = $this->call('quran:import-translation', ['edition' => $slug]);
                $ran++;
                $code === self::SUCCESS ? null : $failures++;
            }

            // Re-assert one default edition per language from the catalog (single
            // source of truth) — robust against per-edition ordering during the run.
            $this->applyCatalogTranslationDefaults();
        }

        if ($doTafsirs) {
            $this->info('=== Tafsir editions ===');
            foreach (config('quran-editions.tafsirs', []) as $entry) {
                $slug = (string) $entry['slug'];
                if ($only && ! in_array($slug, $only, true)) {
                    continue;
                }

                $this->line("→ {$slug}");
                $code = $this->call('quran:import-tafsir', array_filter([
                    'tafsirId' => (int) $entry['quran_com_id'],
                    '--slug' => $slug,
                    '--lang' => $entry['lang'] ?? null,
                    '--name-ar' => $entry['name']['ar'] ?? null,
                    '--name-en' => $entry['name']['en'] ?? null,
                    '--author' => $entry['author'] ?? null,
                ], static fn ($v) => $v !== null && $v !== ''));
                $ran++;
                $code === self::SUCCESS ? null : $failures++;
            }
        }

        $this->newLine();
        $this->info("Done. {$ran} edition(s) processed, {$failures} failure(s).");

        if ($unavailable = config('quran-editions.unavailable_via_api.tafsirs', [])) {
            $this->warn('Not importable via Quran.com API v4 (MCP-only corpus): '
                .implode(', ', array_column($unavailable, 'slug')));
        }

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Ensure exactly one default translation edition per language, per the catalog.
     */
    private function applyCatalogTranslationDefaults(): void
    {
        foreach (config('quran-editions.translations', []) as $entry) {
            if (empty($entry['default'])) {
                continue;
            }

            TranslationResource::where('language', $entry['lang'])->update(['is_default' => false]);
            TranslationResource::where('slug', $entry['slug'])->update(['is_default' => true]);
        }
    }
}
