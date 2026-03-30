<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $plan->exists ? 'Edit' : 'New' }} Membership Plan — Predictor Super Admin</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f5f7fb; color: #172033; }
        main { max-width: 720px; margin: 0 auto; padding: 40px 24px 80px; }
        .breadcrumb { font-size: 0.85rem; color: #5b667a; margin-bottom: 20px; }
        .breadcrumb a { color: #1d4ed8; text-decoration: none; }
        h1 { margin: 0 0 6px; font-size: 1.5rem; }
        .subtitle { margin: 0 0 28px; color: #5b667a; font-size: 0.92rem; }
        .card { background: #fff; border: 1px solid #dbe2ef; border-radius: 18px; padding: 32px; box-shadow: 0 4px 20px rgba(23,32,51,0.06); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 20px; font-size: 0.88rem; font-weight: 600; }
        label span { display: block; margin-bottom: 7px; color: #5b667a; text-transform: uppercase; letter-spacing: 0.04em; font-size: 0.78rem; }
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%; padding: 11px 14px; border: 1px solid #c6d0e1; border-radius: 10px;
            font: inherit; font-size: 0.92rem; background: #fff; color: #172033; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 90px; line-height: 1.6; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,0.1); }
        .toggle-row { display: flex; align-items: center; gap: 12px; }
        .toggle-row input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #1d4ed8; }
        .hint { margin-top: 5px; font-size: 0.8rem; color: #94a3b8; font-weight: 400; }
        .divider { height: 1px; background: #f0f4f8; margin: 24px 0; }
        .form-actions { display: flex; gap: 12px; margin-top: 28px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 12px 24px; border-radius: 10px; font: inherit; font-size: 0.92rem;
            font-weight: 700; cursor: pointer; border: none; text-decoration: none; transition: opacity 160ms;
        }
        .btn:hover { opacity: 0.85; }
        .btn-primary { background: #0f172a; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #172033; }
        .error-box { margin-bottom: 20px; padding: 12px 16px; border-radius: 12px; background: #fee2e2; color: #991b1b; font-size: 0.9rem; }
        .error-box ul { margin: 6px 0 0; padding-left: 18px; }
        .section-title { font-size: 1rem; font-weight: 700; margin: 0 0 16px; }
    </style>
</head>
<body>
<main>
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a> /
        <a href="{{ route('super-admin.memberships.index') }}">Membership Plans</a> /
        {{ $plan->exists ? 'Edit: ' . $plan->name : 'New Plan' }}
    </div>

    <h1>{{ $plan->exists ? 'Edit Membership Plan' : 'New Membership Plan' }}</h1>
    <p class="subtitle">{{ $plan->exists ? 'Update the plan details below.' : 'Fill in the details for the new membership plan.' }}</p>

    @if ($errors->any())
        <div class="error-box">
            <strong>Please fix the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if ($method === 'PUT')
                @method('PUT')
            @endif

            <p class="section-title">Plan Details</p>
            <div class="form-grid">
                <label>
                    <span>Plan Name *</span>
                    <input type="text" name="name" value="{{ old('name', $plan->name) }}" placeholder="e.g. Pro, Enterprise" required>
                </label>

                <label>
                    <span>Slug *</span>
                    <input type="text" name="slug" value="{{ old('slug', $plan->slug) }}" placeholder="e.g. pro, enterprise-yearly" required>
                    <p class="hint">Unique identifier, lowercase, no spaces.</p>
                </label>

                <label class="form-full">
                    <span>Description</span>
                    <textarea name="description" placeholder="Short description visible to users and admins.">{{ old('description', $plan->description) }}</textarea>
                </label>

                <label>
                    <span>Price (USD) *</span>
                    <input type="number" name="price" value="{{ old('price', $plan->price ?? 0) }}" min="0" step="0.01" required>
                    <p class="hint">Set to 0 for free plans.</p>
                </label>

                <label>
                    <span>Billing Period *</span>
                    <select name="billing_period" required>
                        @foreach(['free' => 'Free', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'one_time' => 'One-time'] as $val => $label)
                            <option value="{{ $val }}" {{ old('billing_period', $plan->billing_period) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="divider"></div>
            <p class="section-title">Limits &amp; Permissions</p>

            <div class="form-grid">
                <label>
                    <span>API Token Limit *</span>
                    <input type="number" name="api_token_limit" value="{{ old('api_token_limit', $plan->api_token_limit ?? 1) }}" min="0" required>
                    <p class="hint">Max tokens a user on this plan can create.</p>
                </label>

                <label>
                    <span>Route Query Limit (per day) *</span>
                    <input type="number" name="route_query_limit" value="{{ old('route_query_limit', $plan->route_query_limit ?? 10) }}" min="0" required>
                    <p class="hint">Max API risk queries per day.</p>
                </label>
            </div>

            <div class="divider"></div>
            <p class="section-title">Features</p>

            <label class="form-full">
                <span>Feature List (one per line)</span>
                <textarea name="features_text" placeholder="Access to risk dashboard&#10;Up to 10 route queries/day&#10;1 API token">{{ old('features_text', $plan->features ? implode("\n", $plan->features) : '') }}</textarea>
                <p class="hint">Each line becomes a bullet point on the registration page.</p>
            </label>

            <div class="divider"></div>
            <p class="section-title">Display Settings</p>

            <div class="form-grid">
                <label>
                    <span>Sort Order *</span>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0" required>
                    <p class="hint">Lower = appears first in lists.</p>
                </label>

                <label>
                    <span>Status</span>
                    <div class="toggle-row" style="margin-top: 8px;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
                        <span style="font-size: 0.92rem; font-weight: 500;">Active (visible to users)</span>
                    </div>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    {{ $plan->exists ? 'Save Changes' : 'Create Plan' }}
                </button>
                <a href="{{ route('super-admin.memberships.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
