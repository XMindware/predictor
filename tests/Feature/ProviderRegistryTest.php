<?php

namespace Tests\Feature;

use App\Models\Provider;
use Database\Seeders\ProviderRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_registry_models_persist_credentials_and_configs(): void
    {
        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
            'notes' => 'Default weather provider.',
        ]);

        $credential = $provider->credentials()->create([
            'key' => 'api_key',
            'value' => 'secret-key',
            'is_secret' => true,
        ]);

        $config = $provider->configs()->create([
            'key' => 'timeout_seconds',
            'value' => '10',
        ]);

        $this->assertTrue($credential->provider->is($provider));
        $this->assertTrue($config->provider->is($provider));
        $this->assertTrue($provider->credentials->contains($credential));
        $this->assertTrue($provider->configs->contains($config));
        $this->assertTrue($provider->active);
        $this->assertTrue($credential->is_secret);
        $this->assertSame('10', $config->value);
    }

    public function test_provider_registry_seeder_creates_baseline_providers(): void
    {
        $this->seed(ProviderRegistrySeeder::class);

        $this->assertDatabaseCount('providers', 3);
        $this->assertDatabaseHas('providers', [
            'slug' => 'openweather',
            'service' => 'weather',
            'active' => true,
        ]);
        $this->assertDatabaseHas('providers', [
            'slug' => 'flightstats',
            'service' => 'flights',
            'active' => true,
        ]);
        $this->assertDatabaseHas('provider_credentials', [
            'key' => 'app_id',
            'is_secret' => true,
        ]);
        $this->assertDatabaseHas('provider_credentials', [
            'key' => 'app_key',
            'is_secret' => true,
        ]);
        $this->assertDatabaseHas('provider_configs', [
            'key' => 'base_url',
        ]);
    }
}
