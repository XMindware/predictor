<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): View
    {
        $freePlan = MembershipPlan::where('slug', 'visitor-free')->where('is_active', true)->first();

        return view('auth.register', compact('freePlan'));
    }

    /**
     * Handle registration: always creates a Visitor account with the free plan
     * and loads demo data into the session for the new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'role'     => User::ROLE_VISITOR,
        ]);

        // Assign the free/visitor membership plan
        $freePlan = MembershipPlan::where('slug', 'visitor-free')->first();

        if ($freePlan) {
            UserMembership::create([
                'user_id'            => $user->id,
                'membership_plan_id' => $freePlan->id,
                'status'             => 'active',
                'started_at'         => now(),
                'expires_at'         => now()->addDays(30),
                'notes'              => 'Auto-assigned on visitor registration.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome! Your visitor account has been created with demo data loaded.');
    }
}
