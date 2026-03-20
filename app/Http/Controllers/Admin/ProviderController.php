<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Services\Providers\ProviderAdapterRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProviderController extends Controller
{
    public function index(): View
    {
        return view('admin.providers.index', [
            'providers' => Provider::query()
                ->withCount(['credentials', 'configs'])
                ->orderBy('service')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.providers.form', [
            'provider' => new Provider([
                'active' => true,
            ]),
            'pageTitle' => 'Create Provider',
            'formAction' => route('admin.providers.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        DB::transaction(function () use ($payload): void {
            $provider = Provider::query()->create($payload['provider']);

            $this->syncCredentials($provider, $payload['credentials']);
            $this->syncConfigs($provider, $payload['configs']);
        });

        return redirect()
            ->route('admin.providers.index')
            ->with('status', 'Provider created.');
    }

    public function edit(Provider $provider): View
    {
        $provider->load(['credentials', 'configs']);

        return view('admin.providers.form', [
            'provider' => $provider,
            'pageTitle' => 'Edit Provider',
            'formAction' => route('admin.providers.update', $provider),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $payload = $this->validatedPayload($request, $provider);

        DB::transaction(function () use ($provider, $payload): void {
            $provider->update($payload['provider']);

            $this->syncCredentials($provider, $payload['credentials']);
            $this->syncConfigs($provider, $payload['configs']);
        });

        return redirect()
            ->route('admin.providers.index')
            ->with('status', 'Provider updated.');
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        $provider->delete();

        return redirect()
            ->route('admin.providers.index')
            ->with('status', 'Provider deleted.');
    }

    public function test(Provider $provider, ProviderAdapterRegistry $registry): RedirectResponse
    {
        try {
            $items = match ($provider->service) {
                'weather' => $registry->weather($provider)->fetchWeather([
                    'provider_slug' => $provider->slug,
                    'location_code' => (string) config('operations.base_airport_iata', 'CUN'),
                    'timezone' => 'America/Cancun',
                ]),
                'flights' => $registry->flights($provider)->searchFlights([
                    'provider_slug' => $provider->slug,
                    'origin_code' => 'JFK',
                    'destination_code' => (string) config('operations.base_airport_iata', 'CUN'),
                    'date_window_days' => 7,
                ]),
                'news' => $registry->news($provider)->fetchNews([
                    'provider_slug' => $provider->slug,
                    'headline_context' => sprintf('travel to %s', (string) config('operations.base_airport_iata', 'CUN')),
                ]),
                default => throw new \InvalidArgumentException(sprintf('Unsupported provider service [%s].', $provider->service)),
            };

            return redirect()
                ->route('admin.providers.edit', $provider)
                ->with('status', sprintf(
                    'API test passed for %s. Adapter returned %d item(s).',
                    $provider->name,
                    count($items),
                ));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.providers.edit', $provider)
                ->with('error', sprintf('API test failed for %s: %s', $provider->name, $exception->getMessage()));
        }
    }

    /**
     * @return array{
     *     provider: array<string, mixed>,
     *     credentials: list<array{id: int|null, key: string, value: string|null, is_secret: bool}>,
     *     configs: list<array{id: int|null, key: string, value: string|null}>
     * }
     */
    private function validatedPayload(Request $request, ?Provider $provider = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('providers', 'slug')->ignore($provider?->id)],
            'service' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'credentials' => ['sometimes', 'array'],
            'credentials.*.id' => ['nullable', 'integer'],
            'credentials.*.key' => ['nullable', 'string', 'max:255'],
            'credentials.*.value' => ['nullable', 'string'],
            'credentials.*.is_secret' => ['sometimes', 'boolean'],
            'configs' => ['sometimes', 'array'],
            'configs.*.id' => ['nullable', 'integer'],
            'configs.*.key' => ['nullable', 'string', 'max:255'],
            'configs.*.value' => ['nullable', 'string'],
        ]);

        return [
            'provider' => [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'service' => $validated['service'],
                'driver' => $validated['driver'],
                'active' => $request->boolean('active'),
                'notes' => $validated['notes'] ?? null,
            ],
            'credentials' => $this->normalizeCredentials($validated['credentials'] ?? []),
            'configs' => $this->normalizeConfigs($validated['configs'] ?? []),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{id: int|null, key: string, value: string|null, is_secret: bool}>
     */
    private function normalizeCredentials(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                return [
                    'id' => isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null,
                    'key' => trim((string) ($row['key'] ?? '')),
                    'value' => isset($row['value']) && $row['value'] !== '' ? (string) $row['value'] : null,
                    'is_secret' => filter_var($row['is_secret'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            })
            ->filter(fn (array $row): bool => $row['key'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{id: int|null, key: string, value: string|null}>
     */
    private function normalizeConfigs(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                return [
                    'id' => isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null,
                    'key' => trim((string) ($row['key'] ?? '')),
                    'value' => isset($row['value']) && $row['value'] !== '' ? (string) $row['value'] : null,
                ];
            })
            ->filter(fn (array $row): bool => $row['key'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  list<array{id: int|null, key: string, value: string|null, is_secret: bool}>  $rows
     */
    private function syncCredentials(Provider $provider, array $rows): void
    {
        $retainedIds = collect($rows)
            ->map(function (array $row) use ($provider): int {
                if ($row['id']) {
                    $credential = $provider->credentials()->findOrFail($row['id']);
                    $credential->update([
                        'key' => $row['key'],
                        'value' => $row['value'],
                        'is_secret' => $row['is_secret'],
                    ]);

                    return $credential->id;
                }

                return $provider->credentials()->create([
                    'key' => $row['key'],
                    'value' => $row['value'],
                    'is_secret' => $row['is_secret'],
                ])->id;
            })
            ->all();

        $provider->credentials()
            ->when(
                $retainedIds === [],
                fn ($query) => $query,
                fn ($query) => $query->whereNotIn('id', $retainedIds)
            )
            ->delete();
    }

    /**
     * @param  list<array{id: int|null, key: string, value: string|null}>  $rows
     */
    private function syncConfigs(Provider $provider, array $rows): void
    {
        $retainedIds = collect($rows)
            ->map(function (array $row) use ($provider): int {
                if ($row['id']) {
                    $config = $provider->configs()->findOrFail($row['id']);
                    $config->update([
                        'key' => $row['key'],
                        'value' => $row['value'],
                    ]);

                    return $config->id;
                }

                return $provider->configs()->create([
                    'key' => $row['key'],
                    'value' => $row['value'],
                ])->id;
            })
            ->all();

        $provider->configs()
            ->when(
                $retainedIds === [],
                fn ($query) => $query,
                fn ($query) => $query->whereNotIn('id', $retainedIds)
            )
            ->delete();
    }
}
