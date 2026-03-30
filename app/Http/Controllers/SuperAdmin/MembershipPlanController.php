<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(): View
    {
        $plans = MembershipPlan::orderBy('sort_order')->orderBy('id')->get();

        return view('super-admin.memberships.index', compact('plans'));
    }

    public function create(): View
    {
        return view('super-admin.memberships.form', [
            'plan'   => new MembershipPlan(),
            'action' => route('super-admin.memberships.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        MembershipPlan::create($validated);

        return redirect()->route('super-admin.memberships.index')
            ->with('status', 'Membership plan created successfully.');
    }

    public function edit(MembershipPlan $membership): View
    {
        return view('super-admin.memberships.form', [
            'plan'   => $membership,
            'action' => route('super-admin.memberships.update', $membership),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, MembershipPlan $membership): RedirectResponse
    {
        $validated = $this->validatePlan($request, $membership->id);

        $membership->update($validated);

        return redirect()->route('super-admin.memberships.index')
            ->with('status', 'Membership plan updated successfully.');
    }

    public function destroy(MembershipPlan $membership): RedirectResponse
    {
        $membership->delete();

        return redirect()->route('super-admin.memberships.index')
            ->with('status', 'Membership plan deleted.');
    }

    private function validatePlan(Request $request, ?int $excludeId = null): array
    {
        $featuresRaw = $request->input('features_text', '');
        $features = array_filter(
            array_map('trim', explode("\n", $featuresRaw)),
            fn ($line) => $line !== ''
        );

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'slug'               => ['required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('membership_plans', 'slug')->ignore($excludeId)],
            'description'        => ['nullable', 'string', 'max:1000'],
            'price'              => ['required', 'numeric', 'min:0'],
            'billing_period'     => ['required', 'in:free,monthly,yearly,one_time'],
            'api_token_limit'    => ['required', 'integer', 'min:0'],
            'route_query_limit'  => ['required', 'integer', 'min:0'],
            'is_active'          => ['sometimes', 'boolean'],
            'sort_order'         => ['required', 'integer', 'min:0'],
        ]);

        $validated['features'] = array_values($features);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
