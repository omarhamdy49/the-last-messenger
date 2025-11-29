<?php

declare(strict_types=1);

namespace App\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HTTP Utility for third-party API integrations
 *
 * Supports multiple authentication methods, retry logic,
 * rate limiting, and comprehensive error handling.
 */
class HttpUtil
{
    private Client $client;

    private array $defaultHeaders = [];

    private ?string $baseUrl = null;

    private int $timeout = 30;

    private int $retryAttempts = 3;

    private int $retryDelay = 1000; // milliseconds

    private int $rateLimitDelay = 0; // milliseconds between requests

    private float $lastRequestTime = 0;

    private bool $enableLogging = true;

    private ?string $logChannel = null;

    /**
     * Authentication types
     */
    public const AUTH_NONE = 'none';

    public const AUTH_BEARER = 'bearer';

    public const AUTH_BASIC = 'basic';

    public const AUTH_API_KEY = 'api_key';

    public const AUTH_OAUTH2 = 'oauth2';

    public const AUTH_DIGEST = 'digest';

    public const AUTH_CUSTOM = 'custom';

    /**
     * API Key locations
     */
    public const API_KEY_HEADER = 'header';

    public const API_KEY_QUERY = 'query';

    private string $authType = self::AUTH_NONE;

    private array $authConfig = [];

    /**
     * Create a new HTTP client instance
     */
    public function __construct(?string $baseUrl = null, array $options = [])
    {
        $this->baseUrl = $baseUrl;
        $this->timeout = $options['timeout'] ?? 30;
        $this->retryAttempts = $options['retry_attempts'] ?? 3;
        $this->retryDelay = $options['retry_delay'] ?? 1000;
        $this->rateLimitDelay = $options['rate_limit_delay'] ?? 0;
        $this->enableLogging = $options['enable_logging'] ?? true;
        $this->logChannel = $options['log_channel'] ?? null;

        $this->initializeClient($options);
    }

    /**
     * Create a client with bearer token authentication
     */
    public static function withBearerToken(string $baseUrl, string $token, array $options = []): self
    {
        $instance = new self($baseUrl, $options);
        $instance->setAuthBearer($token);

        return $instance;
    }

    /**
     * Create a client with basic authentication
     */
    public static function withBasicAuth(string $baseUrl, string $username, string $password, array $options = []): self
    {
        $instance = new self($baseUrl, $options);
        $instance->setAuthBasic($username, $password);

        return $instance;
    }

    /**
     * Create a client with API key authentication
     */
    public static function withApiKey(
        string $baseUrl,
        string $apiKey,
        string $keyName = 'X-API-Key',
        string $location = self::API_KEY_HEADER,
        array $options = []
    ): self {
        $instance = new self($baseUrl, $options);
        $instance->setAuthApiKey($apiKey, $keyName, $location);

        return $instance;
    }

    /**
     * Create a client with OAuth2 authentication
     */
    public static function withOAuth2(
        string $baseUrl,
        string $accessToken,
        ?string $tokenType = 'Bearer',
        array $options = []
    ): self {
        $instance = new self($baseUrl, $options);
        $instance->setAuthOAuth2($accessToken, $tokenType);

        return $instance;
    }

    /**
     * Initialize the Guzzle client with retry middleware
     */
    private function initializeClient(array $options = []): void
    {
        $stack = HandlerStack::create();

        // Add retry middleware
        $stack->push(Middleware::retry(
            $this->createRetryDecider(),
            $this->createRetryDelay()
        ));

        $clientConfig = [
            'handler' => $stack,
            'timeout' => $this->timeout,
            'connect_timeout' => $options['connect_timeout'] ?? 10,
            'http_errors' => false, // Handle errors manually
            'verify' => $options['verify_ssl'] ?? true,
        ];

        if ($this->baseUrl) {
            $clientConfig['base_uri'] = $this->baseUrl;
        }

        $this->client = new Client($clientConfig);
    }

    /**
     * Create retry decision logic
     */
    private function createRetryDecider(): callable
    {
        return function (
            int $retries,
            Request $request,
            ?Response $response = null,
            ?\Exception $exception = null
        ): bool {
            // Don't retry if we've exceeded max attempts
            if ($retries >= $this->retryAttempts) {
                return false;
            }

            // Retry on connection errors
            if ($exception instanceof ConnectException) {
                $this->log('warning', 'Connection error, retrying...', [
                    'attempt' => $retries + 1,
                    'uri' => (string) $request->getUri(),
                ]);

                return true;
            }

            // Retry on specific status codes
            if ($response) {
                $statusCode = $response->getStatusCode();

                // Retry on rate limit (429) or server errors (5xx)
                if ($statusCode === 429 || ($statusCode >= 500 && $statusCode < 600)) {
                    $this->log('warning', "HTTP {$statusCode}, retrying...", [
                        'attempt' => $retries + 1,
                        'uri' => (string) $request->getUri(),
                    ]);

                    return true;
                }
            }

            return false;
        };
    }

    /**
     * Create retry delay calculation
     */
    private function createRetryDelay(): callable
    {
        return function (int $retries, ?Response $response): int {
            // Check for Retry-After header
            if ($response && $response->hasHeader('Retry-After')) {
                $retryAfter = $response->getHeader('Retry-After')[0];

                // If numeric, it's seconds
                if (is_numeric($retryAfter)) {
                    return (int) $retryAfter * 1000;
                }

                // If date, calculate delay
                $retryTime = strtotime($retryAfter);
                if ($retryTime !== false) {
                    return max(0, ($retryTime - time()) * 1000);
                }
            }

            // Exponential backoff: delay * 2^retries
            return $this->retryDelay * (int) pow(2, $retries);
        };
    }

    /**
     * Set bearer token authentication
     */
    public function setAuthBearer(string $token): self
    {
        $this->authType = self::AUTH_BEARER;
        $this->authConfig = ['token' => $token];

        return $this;
    }

    /**
     * Set basic authentication
     */
    public function setAuthBasic(string $username, string $password): self
    {
        $this->authType = self::AUTH_BASIC;
        $this->authConfig = ['username' => $username, 'password' => $password];

        return $this;
    }

    /**
     * Set API key authentication
     */
    public function setAuthApiKey(
        string $apiKey,
        string $keyName = 'X-API-Key',
        string $location = self::API_KEY_HEADER
    ): self {
        $this->authType = self::AUTH_API_KEY;
        $this->authConfig = [
            'key' => $apiKey,
            'name' => $keyName,
            'location' => $location,
        ];

        return $this;
    }

    /**
     * Set OAuth2 authentication
     */
    public function setAuthOAuth2(string $accessToken, string $tokenType = 'Bearer'): self
    {
        $this->authType = self::AUTH_OAUTH2;
        $this->authConfig = [
            'access_token' => $accessToken,
            'token_type' => $tokenType,
        ];

        return $this;
    }

    /**
     * Set custom authentication via callback
     */
    public function setAuthCustom(callable $authCallback): self
    {
        $this->authType = self::AUTH_CUSTOM;
        $this->authConfig = ['callback' => $authCallback];

        return $this;
    }

    /**
     * Set default headers for all requests
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;

        return $this;
    }

    /**
     * Add a default header
     */
    public function addDefaultHeader(string $name, string $value): self
    {
        $this->defaultHeaders[$name] = $value;

        return $this;
    }

    /**
     * Set rate limit delay (milliseconds between requests)
     */
    public function setRateLimitDelay(int $milliseconds): self
    {
        $this->rateLimitDelay = $milliseconds;

        return $this;
    }

    /**
     * Perform a GET request
     */
    public function get(string $uri, array $query = [], array $headers = []): HttpResponse
    {
        return $this->request('GET', $uri, [
            'query' => $query,
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a POST request with JSON body
     */
    public function post(string $uri, array $data = [], array $headers = []): HttpResponse
    {
        return $this->request('POST', $uri, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a POST request with form data
     */
    public function postForm(string $uri, array $data = [], array $headers = []): HttpResponse
    {
        return $this->request('POST', $uri, [
            'form_params' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a POST request with multipart data (file uploads)
     */
    public function postMultipart(string $uri, array $multipart = [], array $headers = []): HttpResponse
    {
        return $this->request('POST', $uri, [
            'multipart' => $multipart,
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a PUT request
     */
    public function put(string $uri, array $data = [], array $headers = []): HttpResponse
    {
        return $this->request('PUT', $uri, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a PATCH request
     */
    public function patch(string $uri, array $data = [], array $headers = []): HttpResponse
    {
        return $this->request('PATCH', $uri, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a DELETE request
     */
    public function delete(string $uri, array $data = [], array $headers = []): HttpResponse
    {
        return $this->request('DELETE', $uri, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a HEAD request
     */
    public function head(string $uri, array $headers = []): HttpResponse
    {
        return $this->request('HEAD', $uri, [
            'headers' => $headers,
        ]);
    }

    /**
     * Perform an OPTIONS request
     */
    public function options(string $uri, array $headers = []): HttpResponse
    {
        return $this->request('OPTIONS', $uri, [
            'headers' => $headers,
        ]);
    }

    /**
     * Perform a raw request with full control
     */
    public function request(string $method, string $uri, array $options = []): HttpResponse
    {
        $this->applyRateLimit();

        // Build request options
        $requestOptions = $this->buildRequestOptions($options);

        $startTime = microtime(true);

        try {
            $response = $this->client->request($method, $uri, $requestOptions);
            $duration = (microtime(true) - $startTime) * 1000;

            $httpResponse = new HttpResponse($response);

            $this->log('info', 'HTTP request completed', [
                'method' => $method,
                'uri' => $uri,
                'status' => $httpResponse->getStatusCode(),
                'duration_ms' => round($duration, 2),
            ]);

            return $httpResponse;
        } catch (RequestException $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('error', 'HTTP request failed', [
                'method' => $method,
                'uri' => $uri,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
            ]);

            if ($e->hasResponse()) {
                return new HttpResponse($e->getResponse());
            }

            throw new HttpException(
                message: $e->getMessage(),
                statusCode: 0,
                previous: $e
            );
        } catch (\Exception $e) {
            $this->log('error', 'HTTP request error', [
                'method' => $method,
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);

            throw new HttpException(
                message: $e->getMessage(),
                statusCode: 0,
                previous: $e
            );
        }
    }

    /**
     * Perform a cached GET request
     */
    public function getCached(
        string $uri,
        array $query = [],
        int $ttlSeconds = 3600,
        ?string $cacheKey = null
    ): HttpResponse {
        $key = $cacheKey ?? $this->generateCacheKey('GET', $uri, $query);

        return Cache::remember($key, $ttlSeconds, function () use ($uri, $query) {
            return $this->get($uri, $query);
        });
    }

    /**
     * Build request options with authentication and headers
     */
    private function buildRequestOptions(array $options): array
    {
        // Merge default headers with request headers
        $headers = array_merge(
            $this->getDefaultHeaders(),
            $options['headers'] ?? []
        );

        // Apply authentication
        $authHeaders = $this->getAuthHeaders();
        $headers = array_merge($headers, $authHeaders);

        $options['headers'] = $headers;

        // Handle API key in query string
        if ($this->authType === self::AUTH_API_KEY &&
            $this->authConfig['location'] === self::API_KEY_QUERY) {
            $options['query'] = array_merge(
                $options['query'] ?? [],
                [$this->authConfig['name'] => $this->authConfig['key']]
            );
        }

        // Apply basic auth
        if ($this->authType === self::AUTH_BASIC) {
            $options['auth'] = [
                $this->authConfig['username'],
                $this->authConfig['password'],
            ];
        }

        // Apply digest auth
        if ($this->authType === self::AUTH_DIGEST) {
            $options['auth'] = [
                $this->authConfig['username'],
                $this->authConfig['password'],
                'digest',
            ];
        }

        return $options;
    }

    /**
     * Get default headers including content type
     */
    private function getDefaultHeaders(): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $this->defaultHeaders);
    }

    /**
     * Get authentication headers based on auth type
     */
    private function getAuthHeaders(): array
    {
        return match ($this->authType) {
            self::AUTH_BEARER => ['Authorization' => 'Bearer '.$this->authConfig['token']],
            self::AUTH_OAUTH2 => ['Authorization' => $this->authConfig['token_type'].' '.$this->authConfig['access_token']],
            self::AUTH_API_KEY => $this->authConfig['location'] === self::API_KEY_HEADER
                ? [$this->authConfig['name'] => $this->authConfig['key']]
                : [],
            self::AUTH_CUSTOM => is_callable($this->authConfig['callback'])
                ? ($this->authConfig['callback'])()
                : [],
            default => [],
        };
    }

    /**
     * Apply rate limiting delay
     */
    private function applyRateLimit(): void
    {
        if ($this->rateLimitDelay <= 0) {
            return;
        }

        $now = microtime(true) * 1000;
        $elapsed = $now - $this->lastRequestTime;

        if ($elapsed < $this->rateLimitDelay && $this->lastRequestTime > 0) {
            $sleep = (int) ceil($this->rateLimitDelay - $elapsed);
            usleep($sleep * 1000);
        }

        $this->lastRequestTime = microtime(true) * 1000;
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey(string $method, string $uri, array $params = []): string
    {
        $data = [
            'method' => $method,
            'base_url' => $this->baseUrl,
            'uri' => $uri,
            'params' => $params,
        ];

        return 'http_cache_'.md5(json_encode($data));
    }

    /**
     * Log a message
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (! $this->enableLogging) {
            return;
        }

        $logger = $this->logChannel
            ? Log::channel($this->logChannel)
            : Log::getFacadeRoot();

        $logger->{$level}("[HttpUtil] {$message}", $context);
    }

    /**
     * Test connection to the base URL
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->head('/');

            return $response->getStatusCode() < 500;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the underlying Guzzle client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
