<?php

namespace Tests\Feature;

use App\Models\ScoringProfile;
use Database\Seeders\ScoringProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoring_profiles_persist_with_json_config_and_active_scope(): void
    {
        $profile = ScoringProfile::create([
            'name' => 'Manual Profile',
            'version' => 'v2',
            'weights' => [
                'flight' => 0.4,
                'weather' => 0.35,
                'news' => 0.2,
                'date_proximity' => 0.05,
            ],
            'thresholds' => [
                'low' => 2.5,
                'medium' => 5.5,
                'high' => 7.5,
            ],
            'active' => true,
        ]);

        $this->assertSame('Manual Profile', $profile->name);
        $this->assertSame('v2', $profile->version);
        $this->assertSame(0.4, $profile->weights['flight']);
        $this->assertSame(7.5, $profile->thresholds['high']);
        $this->assertTrue($profile->active);
        $this->assertTrue(ScoringProfile::query()->active()->sole()->is($profile));
    }

    public function test_scoring_profile_seeder_creates_default_active_profile(): void
    {
        $this->seed(ScoringProfileSeeder::class);

        $profile = ScoringProfile::query()->active()->sole();

        $this->assertSame('Default Risk Profile', $profile->name);
        $this->assertSame('v1', $profile->version);
        $this->assertSame(0.45, $profile->weights['flight']);
        $this->assertSame(0.30, $profile->weights['weather']);
        $this->assertSame(0.20, $profile->weights['news']);
        $this->assertSame(0.05, $profile->weights['date_proximity']);
        $this->assertSame(6, $profile->thresholds['medium']);
    }
}
