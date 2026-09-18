<?php

declare(strict_types=1);

namespace App\Console\Commands\Api;

use App\Models\ApiClient;
use App\Services\ApiClientService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

final class CreateApiClientCommand extends Command
{
    protected $signature = 'api:client:create
        {name : Application name}
        {--scopes=* : Scopes (default: *). Allowed: *, quran, hadith, seerah, media, articles, user}
        {--origins= : Comma-separated allowed origins (scheme://host[:port])}
        {--limit=60 : Requests per minute}';

    protected $description = 'Create a public API client and print its key and secret once';

    public function handle(ApiClientService $clients): int
    {
        $data = [
            'name' => (string) $this->argument('name'),
            'scopes' => (array) $this->option('scopes') ?: ['*'],
            'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) $this->option('origins'))))),
            'rate_limit_per_minute' => (int) $this->option('limit'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['in:'.implode(',', ApiClient::SCOPES)],
            'allowed_origins.*' => ['regex:#^https?://[a-z0-9.-]+(:\d{1,5})?/?$#i'],
            'rate_limit_per_minute' => ['integer', 'min:1', 'max:6000'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        ['client' => $client, 'secret' => $secret] = $clients->create($data, null);

        $this->info("API client #{$client->id} created. Store the secret now; it will not be shown again.");
        $this->line("X-Api-Key: {$client->key}");
        $this->line("X-Api-Secret: {$secret}");

        return self::SUCCESS;
    }
}
