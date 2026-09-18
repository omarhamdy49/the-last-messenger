<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\TokenProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class TokenProviderTest extends TestCase
{
    private const SECRET = 'super-secret-value-9f2c';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'quran.qf.client_id' => 'client-abc',
            'quran.qf.client_secret' => self::SECRET,
            'quran.qf.auth_url' => 'https://oauth2.test/oauth2/token',
        ]);
        Cache::forget('quran:qf:token');
    }

    public function test_it_caches_the_token_and_requests_once(): void
    {
        Http::fake(['oauth2.test/*' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3600])]);

        $provider = app(TokenProvider::class);

        $this->assertSame('tok-1', $provider->token());
        $this->assertSame('tok-1', $provider->token());
        Http::assertSentCount(1);
    }

    public function test_it_sends_client_credentials_with_basic_auth(): void
    {
        Http::fake(['oauth2.test/*' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3600])]);

        app(TokenProvider::class)->token();

        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Basic '.base64_encode('client-abc:'.self::SECRET))
                && $request['grant_type'] === 'client_credentials'
                && $request['scope'] === 'content';
        });
    }

    public function test_forget_forces_a_new_token(): void
    {
        Http::fake(['oauth2.test/*' => Http::sequence()
            ->push(['access_token' => 'tok-1', 'expires_in' => 3600])
            ->push(['access_token' => 'tok-2', 'expires_in' => 3600])]);

        $provider = app(TokenProvider::class);
        $provider->token();
        $provider->forget();

        $this->assertSame('tok-2', $provider->token());
        Http::assertSentCount(2);
    }

    public function test_a_token_close_to_expiry_is_refreshed(): void
    {
        Cache::put('quran:qf:token', ['access_token' => 'old', 'expires_at' => time() + 10], 60);
        Http::fake(['oauth2.test/*' => Http::response(['access_token' => 'fresh', 'expires_in' => 3600])]);

        $this->assertSame('fresh', app(TokenProvider::class)->token());
    }

    public function test_failure_throws_without_leaking_the_secret(): void
    {
        Log::spy();
        Http::fake(['oauth2.test/*' => Http::response(['message' => 'bad', 'type' => 'invalid_token', 'success' => false], 401)]);

        try {
            app(TokenProvider::class)->token();
            $this->fail('Expected QfApiException');
        } catch (QfApiException $e) {
            $this->assertSame('invalid_token', $e->type);
            $this->assertStringNotContainsString(self::SECRET, $e->getMessage());
        }

        Log::shouldNotHaveReceived('info', fn (...$args): bool => str_contains(json_encode($args) ?: '', self::SECRET));
        Log::shouldNotHaveReceived('warning', fn (...$args): bool => str_contains(json_encode($args) ?: '', self::SECRET));
        Log::shouldNotHaveReceived('error', fn (...$args): bool => str_contains(json_encode($args) ?: '', self::SECRET));
    }
}
