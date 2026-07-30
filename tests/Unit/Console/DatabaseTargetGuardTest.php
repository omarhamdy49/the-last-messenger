<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Concerns\GuardsDatabaseTarget;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DatabaseTargetGuardTest extends TestCase
{
    private const ALLOWLIST = ['the_last_messenger_test'];

    public function test_it_aborts_when_the_resolved_database_is_the_main_database(): void
    {
        $this->useDatabase('the_last_messenger');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to run against database [the_last_messenger]');

        $this->guard()->run(self::ALLOWLIST);
    }

    public function test_it_passes_when_the_resolved_database_is_the_test_database(): void
    {
        $this->useDatabase('the_last_messenger_test');

        $this->guard()->run(self::ALLOWLIST);

        $this->addToAssertionCount(1);
    }

    public function test_it_aborts_on_an_empty_allowlist(): void
    {
        $this->useDatabase('the_last_messenger_test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('allowlist is empty');

        $this->guard()->run([]);
    }

    /**
     * Point the default connection at a name without opening a socket:
     * getDatabaseName() reads the resolved config, and the PDO is lazy.
     */
    private function useDatabase(string $database): void
    {
        $default = config('database.default');
        config(["database.connections.{$default}.database" => $database]);
        DB::purge($default);
    }

    private function guard(): object
    {
        return new class
        {
            use GuardsDatabaseTarget;

            /** @param  list<string>  $allowlist */
            public function run(array $allowlist): void
            {
                $this->assertSafeDatabase($allowlist);
            }
        };
    }
}
