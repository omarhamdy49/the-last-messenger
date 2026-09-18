<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import\Concerns;

use App\Domain\Quran\Import\QuranFoundation\Client;
use App\Domain\Quran\Import\QuranFoundation\TokenProvider;
use App\Domain\Quran\Import\Support\Throttle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Points the QF client at fake hosts, pre-seeds a token and removes every sleep, so
 * importer tests only need to fake `apis.test/*` responses.
 */
trait FakesQuranFoundation
{
    protected function setUpQuranFoundation(): void
    {
        config([
            'quran.qf.client_id' => 'client-abc',
            'quran.qf.client_secret' => 'secret-xyz',
            'quran.qf.auth_url' => 'https://oauth2.test/oauth2/token',
            'quran.qf.api_url' => 'https://apis.test/content/api/v4/',
            'quran.qf.reflect_url' => 'https://apis.test/quran-reflect/v1/',
            'quran.qf.audio_base' => 'https://verses.quran.foundation/',
        ]);

        // Never reach the real QF API from a test.
        Http::preventStrayRequests();

        Cache::put('quran:qf:token', ['access_token' => 'tok', 'expires_at' => time() + 3600], 3600);

        $this->app->forgetInstance(Client::class);
        $this->app->singleton(Client::class, fn ($app): Client => new Client(
            $app->make(TokenProvider::class),
            new Throttle(0, static function (int $microseconds): void {}),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function qfFixture(string $name): array
    {
        $path = base_path('tests/Fixtures/qf/'.$name.'.json');

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
