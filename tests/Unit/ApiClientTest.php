<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ApiClient;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ApiClientTest extends TestCase
{
    public function test_scopes(): void
    {
        $client = new ApiClient(['scopes' => ['quran', 'media']]);

        $this->assertTrue($client->hasScope('quran'));
        $this->assertFalse($client->hasScope('hadith'));
        $this->assertTrue((new ApiClient(['scopes' => ['*']]))->hasScope('hadith'));
    }

    public function test_origins(): void
    {
        $client = new ApiClient(['allowed_origins' => ['https://app.example.com/']]);

        $this->assertTrue($client->allowsOrigin(null));
        $this->assertTrue($client->allowsOrigin('https://app.example.com'));
        $this->assertFalse($client->allowsOrigin('https://app.example.com.evil.test'));
        $this->assertTrue((new ApiClient(['allowed_origins' => null]))->allowsOrigin('https://anything.test'));
    }

    public function test_usable_and_secret_matching(): void
    {
        $client = new ApiClient(['is_active' => true, 'expires_at' => Carbon::now()->addDay()]);
        $secret = $client->issueCredentials();

        $this->assertTrue($client->isUsable());
        $this->assertTrue($client->secretMatches($secret));
        $this->assertFalse($client->secretMatches($secret.'x'));
        $this->assertSame(64, strlen($client->secret_hash));
        $this->assertStringNotContainsString($secret, $client->secret_hash);

        $client->expires_at = Carbon::now()->subMinute();
        $this->assertFalse($client->isUsable());
    }
}
