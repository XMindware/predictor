<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\User;
use Database\Seeders\BasicGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProviderCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeLiveProviderResponses();
    }

    public function test_guest_users_are_redirected_away_from_provider_admin_routes(): void
    {
        $this->get('/admin/providers')
            ->assertRedirect('/login');
    }

    public function test_authenticated_users_can_create_provider_records_with_credentials_and_configs(): void
    {
        $user = User::factory()->create();
        $csrf = 'provider-create-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrf])
            ->post(route('admin.providers.store'), [
                '_token' => $csrf,
                'name' => 'OpenWeather',
                'slug' => 'openweather',
                'service' => 'weather',
                'driver' => 'rest',
                'active' => '1',
                'notes' => 'Default provider',
                'credentials' => [
                    [
                        'key' => 'api_key',
                        'value' => 'secret-key',
                        'is_secret' => '1',
                    ],
                ],
                'configs' => [
                    [
                        'key' => 'base_url',
                        'value' => 'https://api.openweathermap.org',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.providers.index'));

        $provider = Provider::where('slug', 'openweather')->firstOrFail();

        $this->assertDatabaseHas('providers', [
            'id' => $provider->id,
            'service' => 'weather',
            'active' => true,
        ]);
        $this->assertDatabaseHas('provider_credentials', [
            'provider_id' => $provider->id,
            'key' => 'api_key',
            'value' => 'secret-key',
            'is_secret' => true,
        ]);
        $this->assertDatabaseHas('provider_configs', [
            'provider_id' => $provider->id,
            'key' => 'base_url',
            'value' => 'https://api.openweathermap.org',
        ]);
    }

    public function test_authenticated_users_can_update_provider_records_and_sync_nested_rows(): void
    {
        $user = User::factory()->create();
        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);
        $credential = $provider->credentials()->create([
            'key' => 'api_key',
            'value' => 'secret-key',
            'is_secret' => true,
        ]);
        $config = $provider->configs()->create([
            'key' => 'base_url',
            'value' => 'https://api.openweathermap.org',
        ]);
        $csrf = 'provider-update-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrf])
            ->put(route('admin.providers.update', $provider), [
                '_token' => $csrf,
                'name' => 'FlightStats',
                'slug' => 'flightstats',
                'service' => 'flights',
                'driver' => 'rest',
                'active' => '0',
                'notes' => 'Flight data provider',
                'credentials' => [
                    [
                        'id' => $credential->id,
                        'key' => 'app_id',
                        'value' => 'flightstats-app-id',
                        'is_secret' => '1',
                    ],
                ],
                'configs' => [
                    [
                        'key' => 'timeout_seconds',
                        'value' => '15',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.providers.index'));

        $provider->refresh();

        $this->assertSame('FlightStats', $provider->name);
        $this->assertSame('flightstats', $provider->slug);
        $this->assertSame('flights', $provider->service);
        $this->assertFalse($provider->active);
        $this->assertDatabaseHas('provider_credentials', [
            'id' => $credential->id,
            'provider_id' => $provider->id,
            'key' => 'app_id',
            'value' => 'flightstats-app-id',
        ]);
        $this->assertDatabaseMissing('provider_configs', [
            'id' => $config->id,
        ]);
        $this->assertDatabaseHas('provider_configs', [
            'provider_id' => $provider->id,
            'key' => 'timeout_seconds',
            'value' => '15',
        ]);
    }

    public function test_authenticated_users_can_delete_provider_records(): void
    {
        $user = User::factory()->create();
        $provider = Provider::create([
            'name' => 'NewsAPI',
            'slug' => 'newsapi',
            'service' => 'news',
            'driver' => 'rest',
            'active' => true,
        ]);
        $credential = $provider->credentials()->create([
            'key' => 'api_key',
            'value' => 'secret',
            'is_secret' => true,
        ]);
        $config = $provider->configs()->create([
            'key' => 'base_url',
            'value' => 'https://newsapi.org',
        ]);
        $csrf = 'provider-delete-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrf])
            ->delete(route('admin.providers.destroy', $provider), [
                '_token' => $csrf,
            ])
            ->assertRedirect(route('admin.providers.index'));

        $this->assertDatabaseMissing('providers', [
            'id' => $provider->id,
        ]);
        $this->assertDatabaseMissing('provider_credentials', [
            'id' => $credential->id,
        ]);
        $this->assertDatabaseMissing('provider_configs', [
            'id' => $config->id,
        ]);
    }

    public function test_authenticated_users_can_run_a_provider_api_smoke_test(): void
    {
        $this->seed(BasicGeographySeeder::class);

        $user = User::factory()->create();
        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);
        $this->configureLiveProvider($provider);
        $csrf = 'provider-test-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrf])
            ->post(route('admin.providers.test', $provider), [
                '_token' => $csrf,
            ])
            ->assertRedirect(route('admin.providers.edit', $provider))
            ->assertSessionHas('status', 'API test passed for OpenWeather. Adapter returned 1 item(s).');
    }
}
