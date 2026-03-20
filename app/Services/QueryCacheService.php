<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class QueryCacheService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return mixed
     */
    public function remember(string $namespace, array $payload, Closure $callback): mixed
    {
        $key = $this->key($namespace, $payload);
        $ttl = (int) config('cache.query_ttl_seconds', 300);

        return Cache::remember($key, now()->addSeconds($ttl), $callback);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function key(string $namespace, array $payload): string
    {
        ksort($payload);

        return sprintf('query-cache:%s:%s', $namespace, sha1(json_encode($payload, JSON_THROW_ON_ERROR)));
    }
}
