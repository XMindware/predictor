<?php

namespace App\Services\Providers\Adapters;

use App\Models\Provider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class ConfiguredHttpProvider
{
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

    private function timeoutSeconds(): int
    {
        return $this->integerConfig('timeout_seconds', 10);
    }
}
