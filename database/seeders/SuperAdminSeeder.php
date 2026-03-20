<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's default superadmin account.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'superadmin@predictor.local')],
            [
                'name' => env('SUPERADMIN_NAME', 'Super Admin'),
                'password' => env('SUPERADMIN_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
