<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Import\QuranFoundation\Client;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

final class ClientTest extends TestCase
{
    use FakesQuranFoundation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
    }

    public function test_it_sends_both_auth_headers(): void
    {
        Http::fake(['apis.test/*' => Http::response(['chapters' => []])]);

        app(Client::class)->get('chapters', ['language' => 'ar']);

        Http::assertSent(fn (Request $r): bool => $r->url() === 'https://apis.test/content/api/v4/chapters?language=ar'
            && $r->hasHeader('x-auth-token', 'tok')
            && $r->hasHeader('x-client-id', 'client-abc'));
    }

    public function test_a_401_refreshes_the_token_and_retries_once(): void
    {
        Http::fake([
            'oauth2.test/*' => Http::response(['access_token' => 'tok-new', 'expires_in' => 3600]),
            'apis.test/*' => Http::sequence()
                ->push(['message' => 'expired', 'type' => 'invalid_token', 'success' => false], 401)
                ->push(['chapters' => [['id' => 1]]]),
        ]);

        $body = app(Client::class)->get('chapters');

        $this->assertSame([['id' => 1]], $body['chapters']);
        Http::assertSentCount(3); // api 401, token, api 200
        Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'apis.test') && $r->hasHeader('x-auth-token', 'tok-new'));
    }

    public function test_a_second_401_is_not_retried_forever(): void
    {
        Http::fake([
            'oauth2.test/*' => Http::response(['access_token' => 'tok-new', 'expires_in' => 3600]),
            'apis.test/*' => Http::response(['type' => 'invalid_token'], 401),
        ]);

        try {
            app(Client::class)->get('chapters');
            $this->fail('Expected QfApiException');
        } catch (QfApiException $e) {
            $this->assertSame(401, $e->status);
        }

        Http::assertSentCount(3);
    }

    public function test_a_429_is_retried_with_retry_after(): void
    {
        Http::fake(['apis.test/*' => Http::sequence()
            ->push(['type' => 'rate_limit_exceeded'], 429, ['Retry-After' => '0'])
            ->push(['ok' => true])]);

        $this->assertSame(['ok' => true], app(Client::class)->get('chapters'));
        Http::assertSentCount(2);
    }

    public function test_a_403_throws_its_type_without_retrying(): void
    {
        Http::fake(['apis.test/*' => Http::response(['message' => 'no', 'type' => 'insufficient_scope', 'success' => false], 403)]);

        try {
            app(Client::class)->get('hadiths/1:1/references');
            $this->fail('Expected QfApiException');
        } catch (QfApiException $e) {
            $this->assertSame('insufficient_scope', $e->type);
            $this->assertSame('hadiths/1:1/references', $e->endpoint);
        }

        Http::assertSentCount(1);
    }

    public function test_persistent_5xx_gives_up_after_the_retry_budget(): void
    {
        config(['quran.qf.max_retries' => 2]);
        Http::fake(['apis.test/*' => Http::response('', 503)]);

        $this->expectException(QfApiException::class);

        try {
            app(Client::class)->get('chapters');
        } finally {
            Http::assertSentCount(3);
        }
    }

    public function test_malformed_json_is_a_payload_exception(): void
    {
        Http::fake(['apis.test/*' => Http::response('<html>oops</html>', 200)]);

        $this->expectException(QfPayloadException::class);

        app(Client::class)->get('chapters');
    }

    public function test_relative_sync_paths_resolve_against_the_content_root(): void
    {
        $this->assertSame(
            'https://apis.test/content/api/v4/resources/snapshots/translations/19',
            app(Client::class)->absoluteUrl('/api/v4/resources/snapshots/translations/19'),
        );
    }
}
