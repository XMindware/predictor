<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Database\Seeder;

class VisitorDemoSeeder extends Seeder
{
    /**
     * Seeds a demo visitor account so new registrants can see what a populated
     * visitor experience looks like. This seeder also demonstrates the membership
     * assignment flow that happens automatically on registration.
     */
    public function run(): void
    {
        // Demo visitor user
        $visitor = User::updateOrCreate(
            ['email' => 'visitor@predictor.local'],
            [
                'name'     => 'Demo Visitor',
                'password' => 'password',
                'role'     => User::ROLE_VISITOR,
            ]
        );

        // Assign the free plan membership to the demo visitor
        $freePlan = MembershipPlan::where('slug', 'visitor-free')->first();

        if ($freePlan && ! $visitor->memberships()->exists()) {
            UserMembership::create([
                'user_id'            => $visitor->id,
                'membership_plan_id' => $freePlan->id,
                'status'             => 'active',
                'started_at'         => now(),
                'expires_at'         => now()->addDays(30),
                'notes'              => 'Demo visitor account — seeded automatically.',
            ]);
        }

        // Demo member user
        $member = User::updateOrCreate(
            ['email' => 'member@predictor.local'],
            [
                'name'     => 'Demo Member',
                'password' => 'password',
                'role'     => User::ROLE_MEMBER,
            ]
        );

        $basicPlan = MembershipPlan::where('slug', 'basic-monthly')->first();

        if ($basicPlan && ! $member->memberships()->exists()) {
            UserMembership::create([
                'user_id'            => $member->id,
                'membership_plan_id' => $basicPlan->id,
                'status'             => 'active',
                'started_at'         => now(),
                'expires_at'         => now()->addMonths(1),
                'notes'              => 'Demo member account — seeded automatically.',
            ]);
        }

        // Demo admin user
        User::updateOrCreate(
            ['email' => 'admin@predictor.local'],
            [
                'name'     => 'Demo Admin',
                'password' => 'password',
                'role'     => User::ROLE_ADMIN,
            ]
        );
    }
}
