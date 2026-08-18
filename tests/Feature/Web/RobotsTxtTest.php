<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Tests\TestCase;

/**
 * robots.txt is an allow-list: named search engines and AI agents may crawl, everything
 * else is denied. It is served dynamically so the Sitemap directive carries the real
 * request host.
 *
 * Note this file is advisory. Compliant crawlers honour it; scrapers ignore it. It is not
 * a security control — nginx rate limiting and Cloudflare's bot rules are.
 */
class RobotsTxtTest extends TestCase
{
    private function body(): string
    {
        $response = $this->get('/robots.txt');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        return $response->getContent() ?: '';
    }

    public function test_named_search_engines_and_ai_agents_are_listed(): void
    {
        $body = $this->body();

        foreach (['Googlebot', 'Bingbot', 'DuckDuckBot', 'Applebot', 'YandexBot'] as $agent) {
            $this->assertStringContainsString("User-agent: {$agent}\n", $body, "missing search engine {$agent}");
        }

        foreach (['GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'CCBot'] as $agent) {
            $this->assertStringContainsString("User-agent: {$agent}\n", $body, "missing AI agent {$agent}");
        }
    }

    /**
     * The whole point of the allow-list: any agent not named above gets nothing.
     */
    public function test_everything_else_is_denied(): void
    {
        $body = $this->body();

        $this->assertStringContainsString("User-agent: *\nDisallow: /", $body);
    }

    public function test_private_surfaces_are_closed_to_allowed_crawlers_too(): void
    {
        $body = $this->body();

        foreach (['/su/portal/', '/api/', '/login', '/profile', '/favorites', '/auth/', '/search'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}\n", $body, "expected {$path} to be disallowed");
        }
    }

    /**
     * An empty `Disallow:` means "no restrictions" and would contradict the specific
     * Disallow lines in the same group. Parsers disagree on how to resolve that, so it must
     * not be emitted — omitting a path is already how you permit it.
     */
    public function test_no_contradictory_empty_disallow_is_emitted(): void
    {
        $this->assertStringNotContainsString("Disallow:\n", $this->body());
    }

    public function test_sitemap_uses_the_request_host(): void
    {
        $this->assertStringContainsString(
            'Sitemap: '.url('/sitemap.xml'),
            $this->body()
        );
    }

    /**
     * Every allowed agent must precede the wildcard group. A `User-agent: *` group placed
     * first would still be a separate group, but keeping the order explicit avoids the
     * classic mistake of putting the catch-all where it reads as global.
     */
    public function test_wildcard_group_comes_last(): void
    {
        $body = $this->body();

        $this->assertGreaterThan(
            strpos($body, 'User-agent: Googlebot'),
            strpos($body, 'User-agent: *'),
            'the wildcard deny group must come after the allow-list'
        );
    }
}
