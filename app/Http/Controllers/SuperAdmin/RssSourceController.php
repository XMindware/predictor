<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\RssNewsSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RssSourceController extends Controller
{
    public function index(Request $request): View
    {
        $filterIata = strtoupper(trim((string) $request->input('iata', '')));

        $query = RssNewsSource::query()->with('provider')->orderByDesc('priority')->orderBy('name');

        if ($filterIata !== '') {
            $query->where('iata', $filterIata);
        }

        $sources = $query->get();

        // Group for sidebar navigation: null key = "Global", then by IATA
        $grouped = $sources->groupBy(fn (RssNewsSource $s): string => $s->iata ?? '__global__');

        // All distinct IATAs for the filter nav
        $allIatas = RssNewsSource::query()
            ->whereNotNull('iata')
            ->distinct()
            ->orderBy('iata')
            ->pluck('iata');

        $providers = Provider::query()->where('service', 'news')->orderBy('name')->get();

        // Default provider for the add form
        $defaultProviderId = Provider::query()->where('slug', 'rss-news')->value('id');

        return view('super-admin.rss-sources.index', compact(
            'sources', 'grouped', 'allIatas', 'filterIata', 'providers', 'defaultProviderId',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'iata'        => ['nullable', 'string', 'size:3'],
            'name'        => ['required', 'string', 'max:120'],
            'url'         => ['required', 'url', 'max:500'],
            'language'    => ['required', 'string', 'max:5'],
            'priority'    => ['required', 'integer', 'min:1', 'max:10'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $validated['iata']      = $validated['iata'] ? strtoupper($validated['iata']) : null;
        $validated['is_active'] = true;

        // Prevent duplicate URL per provider
        $exists = RssNewsSource::query()
            ->where('provider_id', $validated['provider_id'])
            ->where('url', $validated['url'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['url' => 'This feed URL is already registered for that provider.']);
        }

        RssNewsSource::create($validated);

        $label = $validated['iata'] ? "for {$validated['iata']}" : '(global default)';

        return back()->with('status', "Feed \"{$validated['name']}\" added {$label}.");
    }

    public function update(Request $request, RssNewsSource $rssSource): RedirectResponse
    {
        $validated = $request->validate([
            'iata'      => ['nullable', 'string', 'size:3'],
            'name'      => ['required', 'string', 'max:120'],
            'url'       => ['required', 'url', 'max:500'],
            'language'  => ['required', 'string', 'max:5'],
            'priority'  => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['required', 'boolean'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $validated['iata'] = $validated['iata'] ? strtoupper($validated['iata']) : null;

        $rssSource->update($validated);

        return back()->with('status', "Feed \"{$rssSource->name}\" updated.");
    }

    public function toggle(RssNewsSource $rssSource): RedirectResponse
    {
        $rssSource->update(['is_active' => ! $rssSource->is_active]);

        $state = $rssSource->is_active ? 'enabled' : 'disabled';

        return back()->with('status', "Feed \"{$rssSource->name}\" {$state}.");
    }

    public function destroy(RssNewsSource $rssSource): RedirectResponse
    {
        $name = $rssSource->name;
        $rssSource->delete();

        return back()->with('status', "Feed \"{$name}\" removed.");
    }
}
