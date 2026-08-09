<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The public site is Arabic-first: a browser's Accept-Language must not
 * override the Arabic default, but an explicit user choice (session) must.
 */
final class LocaleDefaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_site_defaults_to_arabic_ignoring_browser_language(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/media')
            ->assertOk();

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_explicit_session_locale_overrides_the_default(): void
    {
        $this->withSession(['locale' => 'en'])
            ->withHeader('Accept-Language', 'ar')
            ->get('/media')
            ->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_api_still_negotiates_via_accept_language(): void
    {
        // API clients legitimately content-negotiate via the header.
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
        $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/media/programs');

        $this->assertSame('en', app()->getLocale());
    }
}
