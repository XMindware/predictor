<?php

namespace Database\Seeders;

use App\Models\ScoringProfile;
use Illuminate\Database\Seeder;

class ScoringProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ScoringProfile::query()->updateOrCreate(
            [
                'name' => 'Default Risk Profile',
                'version' => 'v1',
            ],
            [
                'weights' => [
                    'flight' => 0.45,
                    'weather' => 0.30,
                    'news' => 0.20,
                    'date_proximity' => 0.05,
                ],
                'thresholds' => [
                    'low' => 3.0,
                    'medium' => 6.0,
                    'high' => 8.0,
                ],
                'active' => true,
            ]
        );
    }
}
