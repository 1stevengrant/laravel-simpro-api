<?php

namespace StitchDigital\LaravelSimproApi;

use Exception;
use Illuminate\Support\Facades\Cache;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use Saloon\PaginationPlugin\PagedPaginator;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class LaravelSimproApi extends Connector implements Cacheable, HasPagination
{
    use AcceptsJson, AlwaysThrowOnErrors, HasCaching, HasRateLimits;

    private string $baseUrl;

    private string $apiKey;

    // Constants for time values
    private const DEFAULT_MAX_WAIT_TIME_SECONDS = 60;

    private const DEFAULT_DELAY_MILLISECONDS = 1000;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = $baseUrl ?? config('simpro-api.base_url');
        $this->apiKey = $apiKey ?? config('simpro-api.api_key');
        $this->rateLimitingEnabled = config('simpro-api.rate_limit.enabled', true);
    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return $this->baseUrl.'/api/v1.0';
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [];
    }

    /**
     * Default HTTP client options
     */
    protected function defaultConfig(): array
    {
        return [];
    }

    /**
     * Authentication for requests
     */
    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->apiKey);
    }

    /**
     * Resolve rate limits for requests
     *
     * @return array<int, Limit>
     */
    protected function resolveLimits(): array
    {
        return [
            Limit::allow(
                config('simpro-api.rate_limit.per_second'),
                threshold: config('simpro-api.rate_limit.threshold')
            )->everySeconds(1)->sleep(),
        ];
    }

    /**
     * Resolve the rate limit store (cache driver)
     */
    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(Cache::store(config('simpro-api.rate_limit.driver')));
    }

    /**
     * Prefix for rate limiting keys
     */
    protected function getLimiterPrefix(): ?string
    {
        return 'simpro';
    }

    /**
     * Handle 429 Too Many Requests response
     */
    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() !== 429) {
            return;
        }

        $waitTime = self::DEFAULT_MAX_WAIT_TIME_SECONDS;

        $limit->exceeded(
            releaseInSeconds: $waitTime + $this->getRandomDelay()
        );
    }

    /**
     * Handle exceeded rate limit and add a delay
     */
    protected function handleExceededLimit(Limit $limit, PendingRequest $pendingRequest): void
    {
        $existingDelay = $pendingRequest->delay()->get() ?? self::DEFAULT_MAX_WAIT_TIME_SECONDS;
        $totalDelayInMilliseconds = ($existingDelay + $this->getRandomDelay()) * self::DEFAULT_DELAY_MILLISECONDS;

        $pendingRequest->delay()->set($totalDelayInMilliseconds);
    }

    /**
     * Resolve the cache driver for caching responses
     */
    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store(config('simpro-api.cache.driver')));
    }

    /**
     * Cache expiry time in seconds
     */
    public function cacheExpiryInSeconds(): int
    {
        return config('simpro-api.cache.enabled') === false ? 0 : config('simpro-api.cache.expire');
    }

    /**
     * Paginate API requests
     *
     * @param  Request & Paginatable  $request
     *
     * @throws Exception
     */
    public function paginate(Request $request): PagedPaginator
    {
        if (! $request instanceof Paginatable) {
            throw new Exception('The request must implement Paginatable for pagination.');
        }

        return new class(connector: $this, request: $request) extends PagedPaginator
        {
            protected ?int $perPageLimit;

            protected bool $detectInfiniteLoop;

            public function __construct(Connector $connector, Request $request)
            {
                parent::__construct($connector, $request);

                $this->detectInfiniteLoop = config('simpro-api.detect_infinite_loops', true);
                $this->perPageLimit = config('simpro-api.default_pagination_limit', 30);
            }

            protected function onRewind(): void
            {
                $this->currentPage = 1;
            }

            protected function isLastPage(Response $response): bool
            {
                $currentPage = $this->currentPage;
                $totalPages = $this->getTotalPages($response);

                return $currentPage > $totalPages;
            }

            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->json();
            }

            public function getTotalResults(): int
            {
                return (int) $this->currentResponse?->header('Result-Total');

            }

            protected function getTotalPages(Response $response): int
            {
                return (int) $response->header('Result-Pages');
            }

            protected function applyPagination(Request $request): Request
            {
                $request->query()->add('page', $this->currentPage);

                if (isset($this->perPageLimit)) {
                    $request->query()->add('pageSize', $this->perPageLimit);
                }

                return $request;
            }
        };
    }

    /**
     * Generate a random delay between 0 and the default maximum wait time
     */
    protected function getRandomDelay(): int
    {
        return rand(0, self::DEFAULT_MAX_WAIT_TIME_SECONDS);
    }
}
