<?php

namespace App\Services\Providers\Adapters;

use App\Models\Provider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class ConfiguredHttpProvider
{
    /**
     * @var list<array<string, mixed>>
     */
    protected array $exchangeLog = [];

    public function __construct(
        protected readonly Provider $provider,
    ) {
    }

    protected function client(array $headers = []): PendingRequest
    {
        return Http::acceptJson()
            ->timeout($this->timeoutSeconds())
            ->baseUrl($this->requiredConfig('base_url'))
            ->withHeaders($headers);
    }

    protected function requiredCredential(string $key): string
    {
        $value = $this->provider()
            ->credentials
            ->firstWhere('key', $key)
            ?->value;

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf(
                'Provider [%s] is missing credential [%s].',
                $this->provider->slug,
                $key,
            ));
        }

        return trim($value);
    }

    protected function requiredConfig(string $key): string
    {
        $value = $this->optionalConfig($key);

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf(
                'Provider [%s] is missing config [%s].',
                $this->provider->slug,
                $key,
            ));
        }

        return rtrim(trim($value), '/');
    }

    protected function optionalConfig(string $key, mixed $default = null): mixed
    {
        return $this->provider()
            ->configs
            ->firstWhere('key', $key)
            ?->value ?? $default;
    }

    protected function integerConfig(string $key, int $default): int
    {
        return max(1, (int) ($this->optionalConfig($key, (string) $default) ?? $default));
    }

    protected function provider(): Provider
    {
        return $this->provider->loadMissing(['credentials', 'configs']);
    }

    protected function resetExchangeLog(): void
    {
        $this->exchangeLog = [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pullExchangeLog(): array
    {
        $log = $this->exchangeLog;
        $this->exchangeLog = [];

        return $log;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $headers
     */
    protected function recordExchange(
        string $method,
        string $path,
        array $query,
        array $headers,
        Response $response,
    ): void {
        $this->exchangeLog[] = [
            'requested_at' => now()->toIso8601String(),
            'method' => strtoupper($method),
            'url' => $this->requiredConfig('base_url').'/'.ltrim($path, '/'),
            'request' => [
                'query' => $this->redact($query),
                'headers' => $this->redact($headers),
            ],
            'response' => [
                'status' => $response->status(),
                'body' => $this->decodedResponseBody($response),
            ],
        ];
    }

    private function timeoutSeconds(): int
    {
        return $this->integerConfig('timeout_seconds', 10);
    }

    private function decodedResponseBody(Response $response): mixed
    {
        $json = $response->json();

        return $json ?? $response->body();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        $sensitiveKeys = ['appid', 'appkey', 'app_id', 'app_key', 'api_key', 'x-api-key', 'authorization'];

        return collect($values)
            ->mapWithKeys(function (mixed $value, string|int $key) use ($sensitiveKeys): array {
                $normalizedKey = strtolower((string) $key);

                if (in_array($normalizedKey, $sensitiveKeys, true)) {
                    return [(string) $key => '[REDACTED]'];
                }

                return [(string) $key => $value];
            })
            ->all();
    }
}
