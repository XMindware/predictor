<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitored Destinations — Super Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f5f7fb; color: #172033; }
        main { max-width: 1000px; margin: 0 auto; padding: 40px 24px 80px; }

        /* Breadcrumb */
        .breadcrumb { font-size: 0.82rem; color: #5b667a; margin-bottom: 20px; display: flex; align-items: center; gap: 6px; }
        .breadcrumb a { color: #1d4ed8; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: #c6d0e1; }

        /* Cards */
        .card { background: #fff; border: 1px solid #dbe2ef; border-radius: 18px; padding: 24px; box-shadow: 0 4px 24px rgba(23,32,51,0.06); margin-bottom: 24px; }
        .card-title { font-size: 1rem; font-weight: 700; margin: 0 0 4px; }
        .card-sub { font-size: 0.82rem; color: #5b667a; margin: 0 0 20px; }

        /* Page header */
        .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .page-header h1 { margin: 0 0 4px; font-size: 1.5rem; }
        .page-header p { margin: 0; color: #5b667a; font-size: 0.88rem; }

        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 12px; font-size: 0.88rem; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Form elements */
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
        label .hint { font-weight: 400; color: #6b7280; font-size: 0.78rem; }
        input[type=text], input[type=number], select, textarea {
            width: 100%; padding: 9px 12px;
            border: 1px solid #d1d5db; border-radius: 9px;
            font: inherit; font-size: 0.88rem; background: #fff;
            transition: border-color 150ms, box-shadow 150ms;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        textarea { resize: vertical; min-height: 60px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-row.three { grid-template-columns: 2fr 1fr 1fr; }
        .form-field { display: flex; flex-direction: column; gap: 5px; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 9px; font: inherit; font-size: 0.85rem; font-weight: 700; cursor: pointer; border: none; text-decoration: none; transition: opacity 120ms; }
        .btn:hover { opacity: 0.88; }
        .btn-primary   { background: #6366f1; color: #fff; }
        .btn-teal      { background: #1e7a78; color: #fff; }
        .btn-danger    { background: #fee2e2; color: #991b1b; }
        .btn-ghost     { background: #f1f5f9; color: #374151; }
        .btn-sm        { padding: 6px 12px; font-size: 0.78rem; }

        /* Destination cards grid */
        .dest-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .dest-card {
            border: 1px solid #dbe2ef; border-radius: 14px; padding: 18px;
            background: #fafcff; position: relative; transition: border-color 150ms;
        }
        .dest-card.inactive { background: #f8fafc; opacity: 0.72; }
        .dest-card-iata { font-size: 1.8rem; font-weight: 900; letter-spacing: -0.04em; color: #0f172a; }
        .dest-card-city { font-size: 0.82rem; color: #5b667a; margin-top: 2px; }
        .dest-card-meta { font-size: 0.78rem; color: #94a3b8; margin-top: 6px; }
        .dest-card-actions { display: flex; gap: 8px; align-items: center; margin-top: 14px; flex-wrap: wrap; }

        /* Status badge */
        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-active   { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .badge-priority { background: #ede9fe; color: #6d28d9; }

        /* Priority bar */
        .priority-bar { display: flex; gap: 3px; margin-top: 8px; }
        .priority-dot { width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0; }
        .priority-dot.filled { background: #6366f1; }

        /* Inline edit form (hidden by default) */
        .edit-form { display: none; border-top: 1px solid #f0f4f8; margin-top: 14px; padding-top: 14px; }
        .edit-form.open { display: block; }

        /* Empty state */
        .empty { text-align: center; padding: 48px 24px; color: #94a3b8; }
        .empty-icon { font-size: 2.5rem; margin-bottom: 12px; }

        /* Divider */
        .divider { height: 1px; background: #f0f4f8; margin: 20px 0; }
    </style>
</head>
<body>
<main>

    <nav class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>Monitored Destinations</span>
    </nav>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="page-header">
        <div>
            <h1>Monitored Destinations</h1>
            <p>Airports being tracked as base destinations. Adding one provisions all inbound routes and watch targets automatically.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
    </div>

    {{-- ── Add new destination ──────────────────────────────────────────────── --}}
    <div class="card">
        <p class="card-title">Add Destination</p>
        <p class="card-sub">Select an airport from the database. Routes and watch targets will be provisioned instantly.</p>

        <form method="POST" action="{{ route('super-admin.destinations.store') }}">
            @csrf
            <div class="form-row three" style="margin-bottom: 14px;">
                <div class="form-field">
                    <label for="iata">Airport <span class="hint">(IATA code)</span></label>
                    @if ($availableAirports->isNotEmpty())
                        <select name="iata" id="iata" required>
                            <option value="">Select airport…</option>
                            @foreach ($availableAirports as $airport)
                                <option value="{{ $airport->iata }}" {{ old('iata') === $airport->iata ? 'selected' : '' }}>
                                    {{ $airport->iata }}{{ $airport->city ? ' — ' . $airport->city->name : '' }}
                                    {{ $airport->name ? '(' . $airport->name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="iata" id="iata" value="{{ old('iata') }}"
                               placeholder="CUN" maxlength="3" style="text-transform: uppercase" required>
                        <span style="font-size: 0.75rem; color: #6b7280;">All airports are already monitored</span>
                    @endif
                </div>

                <div class="form-field">
                    <label for="label">Display name <span class="hint">(optional)</span></label>
                    <input type="text" name="label" id="label" value="{{ old('label') }}"
                           placeholder="Auto-filled from city">
                </div>

                <div class="form-field">
                    <label for="priority">Priority <span class="hint">(1–10)</span></label>
                    <input type="number" name="priority" id="priority" value="{{ old('priority', 5) }}"
                           min="1" max="10" required>
                </div>
            </div>

            <div class="form-field" style="margin-bottom: 14px;">
                <label for="notes">Notes <span class="hint">(optional)</span></label>
                <textarea name="notes" id="notes" rows="2" placeholder="Internal notes about why this destination is monitored…">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="btn btn-teal">＋ Add &amp; provision routes</button>
        </form>
    </div>

    {{-- ── Existing destinations ────────────────────────────────────────────── --}}
    <div class="card">
        <p class="card-title">Active Monitoring ({{ $destinations->count() }})</p>
        <p class="card-sub">Drag to reprioritize, or edit inline. Deactivating a destination hides it from the impact dashboard without removing its data.</p>

        @if ($destinations->isEmpty())
            <div class="empty">
                <div class="empty-icon">🗺️</div>
                <p>No destinations configured yet. Add one above.</p>
            </div>
        @else
            <div class="dest-grid">
                @foreach ($destinations as $dest)
                    @php
                        $airport = $dest->airport;
                        $city    = $airport?->city;
                    @endphp

                    <div class="dest-card {{ $dest->is_active ? '' : 'inactive' }}" id="dest-{{ $dest->id }}">

                        {{-- Header --}}
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="dest-card-iata">{{ $dest->iata }}</div>
                                <div class="dest-card-city">
                                    {{ $dest->label ?: ($city?->name ?? 'Unknown city') }}
                                    @if ($airport?->name)
                                        <span style="color: #c6d0e1;"> · </span>{{ $airport->name }}
                                    @endif
                                </div>
                            </div>
                            <span class="badge {{ $dest->is_active ? 'badge-active' : 'badge-inactive' }}">
                                {{ $dest->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        {{-- Priority dots --}}
                        <div class="priority-bar" title="Priority {{ $dest->priority }}/10">
                            @for ($i = 1; $i <= 10; $i++)
                                <div class="priority-dot {{ $i <= $dest->priority ? 'filled' : '' }}"></div>
                            @endfor
                        </div>

                        @if ($dest->notes)
                            <div class="dest-card-meta" style="margin-top: 8px;">{{ $dest->notes }}</div>
                        @endif

                        {{-- Action row --}}
                        <div class="dest-card-actions">
                            @if ($dest->is_active)
                                <a href="{{ route('admin.cities.show', $dest->iata) }}" class="btn btn-teal btn-sm">View Impact →</a>
                            @endif

                            <button type="button" class="btn btn-ghost btn-sm"
                                    onclick="toggleEdit({{ $dest->id }})">Edit</button>

                            {{-- Toggle active --}}
                            <form method="POST" action="{{ route('super-admin.destinations.toggle', $dest) }}" style="margin: 0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-sm">
                                    {{ $dest->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('super-admin.destinations.destroy', $dest) }}" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Remove {{ $dest->iata }} from monitoring? Routes and watch targets are kept.')">
                                    Remove
                                </button>
                            </form>
                        </div>

                        {{-- Inline edit --}}
                        <div class="edit-form" id="edit-{{ $dest->id }}">
                            <form method="POST" action="{{ route('super-admin.destinations.update', $dest) }}">
                                @csrf
                                @method('PATCH')

                                <div class="form-field" style="margin-bottom: 10px;">
                                    <label>Display name</label>
                                    <input type="text" name="label" value="{{ $dest->label }}" placeholder="e.g. Cancún">
                                </div>

                                <div class="form-row" style="margin-bottom: 10px;">
                                    <div class="form-field">
                                        <label>Priority (1–10)</label>
                                        <input type="number" name="priority" value="{{ $dest->priority }}" min="1" max="10">
                                    </div>
                                    <div class="form-field">
                                        <label>Status</label>
                                        <select name="is_active">
                                            <option value="1" {{ $dest->is_active ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ !$dest->is_active ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-field" style="margin-bottom: 12px;">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="2">{{ $dest->notes }}</textarea>
                                </div>

                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEdit({{ $dest->id }})">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</main>
<script>
function toggleEdit(id) {
    const form = document.getElementById('edit-' + id);
    form.classList.toggle('open');
}
</script>
</body>
</html>
