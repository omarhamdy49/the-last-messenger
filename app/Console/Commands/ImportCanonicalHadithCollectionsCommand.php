<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Concerns\GuardsDatabaseTarget;
use App\Domain\Hadith\Import\CanonicalHadithImporter;
use Illuminate\Console\Command;
use Throwable;

final class ImportCanonicalHadithCollectionsCommand extends Command
{
    use GuardsDatabaseTarget;

    protected $signature = 'hadith:import-canonical
        {--root= : Canonical hadith-collections directory}
        {--collection=* : Import only named collection(s)}
        {--fresh : Erase all existing Hadith-domain content first}
        {--verify-only : Do not import; verify source/database parity}';

    protected $description = 'Losslessly import and verify the canonical hadith collection corpus';

    public function handle(CanonicalHadithImporter $importer): int
    {
        try {
            $this->assertSafeDatabase(['the_last_messenger', 'the_last_messenger_test']);
            $root = (string) ($this->option('root') ?: base_path('../../docs/hadith-collections'));
            $collections = $this->option('collection') ?: $importer->collections($root);

            if ((bool) $this->option('fresh') && ! (bool) $this->option('verify-only')) {
                $this->warn('Erasing existing Hadith-domain content only.');
                $importer->eraseAll();
            }

            $rows = [];
            foreach ($collections as $code) {
                if (! (bool) $this->option('verify-only')) {
                    $this->line("Importing {$code} …");
                    $importer->importCollection($root, (string) $code);
                }
                $verified = $importer->verifyCollection($root, (string) $code);
                $rows[] = [$code, $verified['books'], $verified['chapters'], $verified['hadiths'], $verified['texts'], $verified['gradings'], $verified['narrators'], 'PASS'];
            }

            $this->table(['collection', 'books', 'chapters', 'hadiths', 'texts', 'gradings', 'narrators', 'verification'], $rows);
            $this->info('Every selected collection matches its canonical source counts.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
