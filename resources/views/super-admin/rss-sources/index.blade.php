<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RSS News Sources — Super Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f0f2f8; color: #172033; }

        /* ── Layout ─────────────────────────────────────────────────────────── */
        .shell { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        .sidebar { background: #fff; border-right: 1px solid #e8edf5; padding: 20px 14px; }
        .main { padding: 28px 32px 64px; max-width: 960px; }

        /* ── Breadcrumb ──────────────────────────────────────────────────────── */
        .breadcrumb { font-size: 0.8rem; color: #6b7280; margin-bottom: 20px; display: flex; gap: 6px; }
        .breadcrumb a { color: #1d4ed8; text-decoration: none; }

        /* ── Page header ─────────────────────────────────────────────────────── */
        .page-head { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
        .page-head h1 { margin: 0 0 4px; font-size: 1.4rem; }
        .page-head p { margin: 0; font-size: 0.82rem; color: #6b7280; }

        /* ── Alerts ──────────────────────────────────────────────────────────── */
        .alert { padding: 11px 16px; border-radius: 11px; font-size: 0.85rem; margin-bottom: 18px; }
        .alert-ok    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* ── Cards ───────────────────────────────────────────────────────────── */
        .card { background: #fff; border: 1px solid #e8edf5; border-radius: 16px; padding: 20px 22px; box-shadow: 0 2px 12px rgba(23,32,51,0.05); margin-bottom: 22px; }
        .card-title { font-size: 0.9rem; font-weight: 700; margin: 0 0 14px; }

        /* ── Add form ────────────────────────────────────────────────────────── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-grid.three { grid-template-columns: 2fr 1fr 1fr; }
        .form-field { display: flex; flex-direction: column; gap: 4px; }
        .form-field.full { grid-column: 1 / -1; }
        .form-label { font-size: 0.78rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.04em; }
        input[type=text], input[type=url], input[type=number], select, textarea {
            padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 8px;
            font: inherit; font-size: 0.85rem; background: #fff; width: 100%;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        textarea { resize: vertical; min-height: 52px; }

        /* ── Buttons ─────────────────────────────────────────────────────────── */
        .btn { display: inline-flex; align-items: center; gap: 5px; padding: 8px 14px; border-radius: 8px; font: inherit; font-size: 0.82rem; font-weight: 700; cursor: pointer; border: none; text-decoration: none; transition: opacity 120ms; }
        .btn:hover { opacity: 0.85; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-teal    { background: #0f766e; color: #fff; }
        .btn-ghost   { background: #f1f5f9; color: #374151; }
        .btn-danger  { background: #fee2e2; color: #991b1b; }
        .btn-sm      { padding: 5px 10px; font-size: 0.75rem; }

        /* ── Sidebar nav ─────────────────────────────────────────────────────── */
        .nav-section { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; padding: 14px 8px 5px; margin-top: 6px; }
        .nav-section:first-child { margin-top: 0; padding-top: 4px; }
        .nav-link { display: flex; align-items: center; gap: 7px; padding: 7px 10px; border-radius: 8px; text-decoration: none; font-size: 0.82rem; font-weight: 500; color: #374151; transition: background 120ms; }
        .nav-link:hover { background: #f0f4ff; color: #1d4ed8; }
        .nav-link.active { background: #eff6ff; color: #1d4ed8; font-weight: 700; }
        .nav-count { margin-left: auto; font-size: 0.7rem; background: #f1f5f9; color: #64748b; padding: 1px 7px; border-radius: 999px; font-weight: 600; }
        .nav-count.has { background: #ede9fe; color: #6d28d9; }

        /* ── Source table ────────────────────────────────────────────────────── */
        .source-group { margin-bottom: 28px; }
        .group-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .group-iata { font-size: 1.3rem; font-weight: 900; letter-spacing: -0.04em; color: #0f172a; }
        .group-label { font-size: 0.78rem; color: #6b7280; }
        .group-count { font-size: 0.72rem; background: #ede9fe; color: #6d28d9; padding: 2px 9px; border-radius: 999px; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 7px 12px; border-bottom: 2px solid #e8edf5; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f4f8; font-size: 0.83rem; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .url-cell { color: #6b7280; font-size: 0.75rem; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .url-cell a { color: #1d4ed8; text-decoration: none; }
        .url-cell a:hover { text-decoration: underline; }

        /* ── Badges ──────────────────────────────────────────────────────────── */
        .badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge-active   { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .badge-en { background: #dbeafe; color: #1d4ed8; }
        .badge-es { background: #fef3c7; color: #92400e; }
        .badge-fr { background: #f3e8ff; color: #7c3aed; }
        .badge-lang { background: #f0f9ff; color: #0369a1; }

        /* ── Priority dots ───────────────────────────────────────────────────── */
        .prio { display: flex; gap: 2px; }
        .prio-dot { width: 7px; height: 7px; border-radius: 50%; background: #e2e8f0; }
        .prio-dot.on { background: #6366f1; }

        /* ── Inline edit ─────────────────────────────────────────────────────── */
        .edit-row { display: none; background: #fafbff; }
        .edit-row.open { display: table-row; }
        .edit-panel { padding: 14px 16px; border-top: 1px solid #e8edf5; }

        /* ── Empty state ─────────────────────────────────────────────────────── */
        .empty { padding: 32px; text-align: center; color: #94a3b8; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="shell">

    {{-- ── SIDEBAR ──────────────────────────────────────────────────────────── --}}
    <aside class="sidebar">
        <div class="nav-section">Navigation</div>
        <a href="{{ route('dashboard') }}" class="nav-link">⊞ Dashboard</a>
        <a href="{{ route('super-admin.destinations.index') }}" class="nav-link">🗺️ Destinations</a>
        <a href="{{ route('super-admin.memberships.index') }}" class="nav-link">💳 Memberships</a>
        <a href="{{ route('super-admin.users.index') }}" class="nav-link">👥 Users</a>

        <div class="nav-section">Filter by city</div>
        <a href="{{ route('super-admin.rss-sources.index') }}"
           class="nav-link {{ $filterIata === '' ? 'active' : '' }}">
            All sources
            <span class="nav-count has">{{ $sources->count() }}</span>
        </a>
        <a href="{{ route('super-admin.rss-sources.index', ['iata' => '__global__']) }}"
           class="nav-link {{ $filterIata === '__GLOBAL__' ? 'active' : '' }}" style="font-style:italic;">
            Global defaults
            <span class="nav-count">{{ $sources->whereNull('iata')->count() }}</span>
        </a>

        @foreach ($allIatas as $iata)
            @php $cnt = $sources->where('iata', $iata)->count(); @endphp
            <a href="{{ route('super-admin.rss-sources.index', ['iata' => $iata]) }}"
               class="nav-link {{ $filterIata === $iata ? 'active' : '' }}">
                {{ $iata }}
                <span class="nav-count {{ $cnt > 0 ? 'has' : '' }}">{{ $cnt }}</span>
            </a>
        @endforeach
    </aside>

    {{-- ── MAIN ─────────────────────────────────────────────────────────────── --}}
    <main class="main">

        <nav class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span>›</span>
            <span>RSS News Sources</span>
            @if ($filterIata)
                <span>›</span>
                <span>{{ $filterIata === '__GLOBAL__' ? 'Global defaults' : $filterIata }}</span>
            @endif
        </nav>

        @if (session('status'))
            <div class="alert alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="page-head">
            <div>
                <h1>RSS News Sources</h1>
                <p>{{ $sources->count() }} source{{ $sources->count() !== 1 ? 's' : '' }} — one row per feed URL.
                   Global defaults run on every watch target; city feeds run only when that IATA appears in the criteria.</p>
            </div>
        </div>

        {{-- ── ADD NEW SOURCE ─────────────────────────────────────────────── --}}
        <div class="card">
            <p class="card-title">Add RSS Feed</p>
            <form method="POST" action="{{ route('super-admin.rss-sources.store') }}">
                @csrf

                <div class="form-grid three" style="margin-bottom: 12px;">
                    <div class="form-field">
                        <label class="form-label">Feed name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Miami Herald" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Airport IATA <span style="font-weight:400;color:#9ca3af;">(blank = global)</span></label>
                        <input type="text" name="iata" value="{{ old('iata', $filterIata !== '__GLOBAL__' ? $filterIata : '') }}"
                               maxlength="3" placeholder="MIA" style="text-transform:uppercase">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Provider *</label>
                        <select name="provider_id" required>
                            @foreach ($providers as $p)
                                <option value="{{ $p->id }}" {{ $p->id == $defaultProviderId ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->slug }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-field full" style="margin-bottom: 12px;">
                    <label class="form-label">Feed URL *</label>
                    <input type="url" name="url" value="{{ old('url') }}" placeholder="https://example.com/feed/" required>
                </div>

                <div class="form-grid" style="margin-bottom: 14px;">
                    <div class="form-field">
                        <label class="form-label">Language</label>
                        <select name="language">
                            <option value="en" {{ old('language','en') === 'en' ? 'selected' : '' }}>English (en)</option>
                            <option value="es" {{ old('language') === 'es' ? 'selected' : '' }}>Spanish (es)</option>
                            <option value="fr" {{ old('language') === 'fr' ? 'selected' : '' }}>French (fr)</option>
                            <option value="pt" {{ old('language') === 'pt' ? 'selected' : '' }}>Portuguese (pt)</option>
                            <option value="de" {{ old('language') === 'de' ? 'selected' : '' }}>German (de)</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Priority (1–10)</label>
                        <input type="number" name="priority" value="{{ old('priority', 5) }}" min="1" max="10" required>
                    </div>
                </div>

                <div class="form-field full" style="margin-bottom: 14px;">
                    <label class="form-label">Notes <span style="font-weight:400;color:#9ca3af;">(optional)</span></label>
                    <textarea name="notes" placeholder="What this feed covers…">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">＋ Add feed</button>
            </form>
        </div>

        {{-- ── SOURCE GROUPS ───────────────────────────────────────────────── --}}
        @php
            // Normalise the grouped keys: '__global__' → null internally
            $displayGroups = collect();
            if ($grouped->has('__global__')) {
                $displayGroups->put('__global__', $grouped->get('__global__'));
            }
            foreach ($grouped->keys()->filter(fn($k) => $k !== '__global__')->sort() as $iata) {
                $displayGroups->put($iata, $grouped->get($iata));
            }
        @endphp

        @forelse ($displayGroups as $groupKey => $groupSources)
            @php
                $isGlobal = $groupKey === '__global__';
                $label    = $isGlobal ? 'Global defaults — fetched for every watch target' : '';
            @endphp

            <div class="source-group">
                <div class="group-header">
                    <span class="group-iata">{{ $isGlobal ? '🌐' : $groupKey }}</span>
                    @if (! $isGlobal)
                        <span class="group-label">city-specific</span>
                    @else
                        <span class="group-label">{{ $label }}</span>
                    @endif
                    <span class="group-count">{{ $groupSources->count() }} feed{{ $groupSources->count() !== 1 ? 's' : '' }}</span>
                </div>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>URL</th>
                                <th>Lang</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupSources as $source)
                                <tr>
                                    <td>
                                        <strong>{{ $source->name }}</strong>
                                        @if ($source->notes)
                                            <div style="font-size:0.74rem;color:#94a3b8;margin-top:2px;">{{ $source->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="url-cell">
                                        <a href="{{ $source->url }}" target="_blank" rel="noopener">
                                            {{ $source->url }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $source->language }}">{{ $source->language }}</span>
                                    </td>
                                    <td>
                                        <div class="prio" title="{{ $source->priority }}/10">
                                            @for ($i = 1; $i <= 10; $i++)
                                                <div class="prio-dot {{ $i <= $source->priority ? 'on' : '' }}"></div>
                                            @endfor
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $source->is_active ? 'badge-active' : 'badge-inactive' }}">
                                            {{ $source->is_active ? 'Active' : 'Off' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                            <button type="button" class="btn btn-ghost btn-sm"
                                                    onclick="toggleEdit('edit-{{ $source->id }}')">Edit</button>

                                            <form method="POST" action="{{ route('super-admin.rss-sources.toggle', $source) }}" style="margin:0">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm">
                                                    {{ $source->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('super-admin.rss-sources.destroy', $source) }}" style="margin:0">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Remove \'{{ addslashes($source->name) }}\'?')">✕</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Inline edit row --}}
                                <tr class="edit-row" id="edit-{{ $source->id }}">
                                    <td colspan="6">
                                        <div class="edit-panel">
                                            <form method="POST" action="{{ route('super-admin.rss-sources.update', $source) }}">
                                                @csrf @method('PATCH')

                                                <div class="form-grid three" style="margin-bottom: 10px;">
                                                    <div class="form-field">
                                                        <label class="form-label">Name *</label>
                                                        <input type="text" name="name" value="{{ $source->name }}" required>
                                                    </div>
                                                    <div class="form-field">
                                                        <label class="form-label">IATA <span style="font-weight:400;color:#9ca3af;">(blank = global)</span></label>
                                                        <input type="text" name="iata" value="{{ $source->iata }}" maxlength="3" style="text-transform:uppercase">
                                                    </div>
                                                    <div class="form-field">
                                                        <label class="form-label">Status</label>
                                                        <select name="is_active">
                                                            <option value="1" {{ $source->is_active ? 'selected' : '' }}>Active</option>
                                                            <option value="0" {{ ! $source->is_active ? 'selected' : '' }}>Disabled</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-field full" style="margin-bottom: 10px;">
                                                    <label class="form-label">URL *</label>
                                                    <input type="url" name="url" value="{{ $source->url }}" required>
                                                </div>

                                                <div class="form-grid" style="margin-bottom: 10px;">
                                                    <div class="form-field">
                                                        <label class="form-label">Language</label>
                                                        <select name="language">
                                                            @foreach (['en','es','fr','pt','de'] as $lang)
                                                                <option value="{{ $lang }}" {{ $source->language === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-field">
                                                        <label class="form-label">Priority</label>
                                                        <input type="number" name="priority" value="{{ $source->priority }}" min="1" max="10">
                                                    </div>
                                                </div>

                                                <div class="form-field full" style="margin-bottom: 12px;">
                                                    <label class="form-label">Notes</label>
                                                    <textarea name="notes">{{ $source->notes }}</textarea>
                                                </div>

                                                <div style="display:flex;gap:8px;">
                                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEdit('edit-{{ $source->id }}')">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="empty">No RSS sources found. Add one above.</div>
            </div>
        @endforelse

    </main>
</div>
<script>
function toggleEdit(id) {
    const row = document.getElementById(id);
    row.classList.toggle('open');
}
</script>
</body>
</html>
