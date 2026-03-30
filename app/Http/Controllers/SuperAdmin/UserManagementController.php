<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('activeMembership.plan')->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->paginate(25)->withQueryString();

        return view('super-admin.users.index', [
            'users' => $users,
            'roles' => User::ROLES,
        ]);
    }

    public function show(User $user): View
    {
        $user->load('memberships.plan');
        $plans = MembershipPlan::active()->get();

        return view('super-admin.users.show', compact('user', 'plans'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:' . implode(',', User::ROLES)],
        ]);

        // Prevent downgrading the last super admin
        if ($user->isSuperAdmin() && $request->role !== User::ROLE_SUPER_ADMIN) {
            $otherSuperAdmins = User::where('role', User::ROLE_SUPER_ADMIN)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperAdmins === 0) {
                return back()->withErrors(['role' => 'Cannot demote the last super admin.']);
            }
        }

        $user->update(['role' => $request->role]);

        return back()->with('status', "User role updated to {$request->role}.");
    }

    public function assignMembership(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'status'             => ['required', 'in:active,trial,cancelled,expired'],
            'expires_at'         => ['nullable', 'date', 'after:today'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        UserMembership::create([
            'user_id'            => $user->id,
            'membership_plan_id' => $request->membership_plan_id,
            'status'             => $request->status,
            'started_at'         => now(),
            'expires_at'         => $request->expires_at,
            'notes'              => $request->notes,
        ]);

        return back()->with('status', 'Membership assigned successfully.');
    }

    public function cancelMembership(Request $request, User $user, UserMembership $membership): RedirectResponse
    {
        if ($membership->user_id !== $user->id) {
            abort(403);
        }

        $membership->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('status', 'Membership cancelled.');
    }
}
