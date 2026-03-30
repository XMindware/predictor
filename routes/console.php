<?php

use App\Jobs\BuildAirportIndicatorsJob;
use App\Jobs\BuildCityIndicatorsJob;
use App\Jobs\BuildRouteIndicatorsJob;
use App\Jobs\FetchFlightDataJob;
use App\Jobs\FetchNewsDataJob;
use App\Jobs\FetchWeatherDataJob;
use App\Jobs\NormalizeFlightPayloadJob;
use App\Jobs\NormalizeNewsPayloadJob;
use App\Jobs\NormalizeWeatherPayloadJob;
use App\Jobs\RecordQueueHeartbeat;
use App\Jobs\WarmPopularRoutesCacheJob;
use App\Models\Airport;
use App\Models\FlightEvent;
use App\Models\RawProviderPayload;
use App\Models\RiskQuerySnapshot;
use App\Models\RouteIndicator;
use App\Models\User;
use App\Models\WatchTarget;
use App\Services\OperationsMonitoringService;
use App\Services\StaleDataCheckService;
use App\Support\PlatformHealth;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$resolveUserFromConsole = function (string $identifier): ?User {
    $identifier = trim($identifier);

    if ($identifier === '') {
        return null;
    }

    $query = User::query();

    if (ctype_digit($identifier)) {
        return $query->find((int) $identifier);
    }

    return $query->where('email', $identifier)->first();
};

Artisan::command(
    'users:list {--role= : Filter by role} {--search= : Filter by name or email}',
    function () use ($resolveUserFromConsole): int {
        $query = User::query()->orderBy('id');

        if ($role = $this->option('role')) {
            if (! in_array($role, User::ROLES, true)) {
                $this->error('Invalid role. Allowed roles: '.implode(', ', User::ROLES));

                return 1;
            }

            $query->where('role', $role);
        }

        if ($search = trim((string) $this->option('search'))) {
            $needle = '%'.mb_strtolower($search).'%';

            $query->where(function ($q) use ($needle): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$needle]);
            });
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return 0;
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Verified'],
            $users->map(fn (User $user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->role,
                $user->email_verified_at ? 'yes' : 'no',
            ])->all()
        );

        $this->line('Total: '.$users->count().' user(s)');

        return 0;
    }
)->purpose('List users with optional role and search filters');

Artisan::command(
    'users:update-password {user : User ID or email} {password : New password}',
    function () use ($resolveUserFromConsole): int {
        $identifier = (string) $this->argument('user');
        $password = (string) $this->argument('password');
        $user = $resolveUserFromConsole($identifier);

        if (! $user) {
            $this->error("User [{$identifier}] not found.");

            return 1;
        }

        if (mb_strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return 1;
        }

        $user->update(['password' => $password]);

        $this->info("Password updated for {$user->email}.");

        return 0;
    }
)->purpose('Update a user password by ID or email');

Artisan::command(
    'users:set-role {user : User ID or email} {role : '.User::ROLE_SUPER_ADMIN.'|'.User::ROLE_ADMIN.'|'.User::ROLE_MEMBER.'|'.User::ROLE_VISITOR.'}',
    function () use ($resolveUserFromConsole): int {
        $identifier = (string) $this->argument('user');
        $role = (string) $this->argument('role');
        $user = $resolveUserFromConsole($identifier);

        if (! $user) {
            $this->error("User [{$identifier}] not found.");

            return 1;
        }

        if (! in_array($role, User::ROLES, true)) {
            $this->error('Invalid role. Allowed roles: '.implode(', ', User::ROLES));

            return 1;
        }

        if ($user->isSuperAdmin() && $role !== User::ROLE_SUPER_ADMIN) {
            $otherSuperAdmins = User::query()
                ->where('role', User::ROLE_SUPER_ADMIN)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperAdmins === 0) {
                $this->error('Cannot demote the last super admin.');

                return 1;
            }
        }

        $user->update(['role' => $role]);

        $this->info("Updated {$user->email} role to {$role}.");

        return 0;
    }
)->purpose('Update a user role by ID or email');

Artisan::command('ingestion:fetch-weather', function (): int {
    FetchWeatherDataJob::dispatch();

    $this->info('Dispatched weather ingestion job.');

    return 0;
})->purpose('Queue weather ingestion');

Artisan::command('ingestion:fetch-flights', function (): int {
    FetchFlightDataJob::dispatch();

    $this->info('Dispatched flight ingestion job.');

    return 0;
})->purpose('Queue flight ingestion');

Artisan::command('ingestion:fetch-news', function (): int {
    FetchNewsDataJob::dispatch();

    $this->info('Dispatched news ingestion job.');

    return 0;
})->purpose('Queue news ingestion');

Artisan::command('ingestion:retry-normalization {--limit=100} {--grace-minutes=10}', function (): int {
    $graceMinutes = max(0, (int) $this->option('grace-minutes'));
    $limit = max(1, (int) $this->option('limit'));

    $payloads = RawProviderPayload::query()
        ->whereNull('normalized_at')
        ->where('fetched_at', '<=', now()->subMinutes($graceMinutes))
        ->orderBy('fetched_at')
        ->limit($limit)
        ->get();

    $dispatched = 0;

    foreach ($payloads as $payload) {
        match ($payload->source_type) {
            'weather' => NormalizeWeatherPayloadJob::dispatch($payload->id),
            'flight', 'flights' => NormalizeFlightPayloadJob::dispatch($payload->id),
            'news' => NormalizeNewsPayloadJob::dispatch($payload->id),
            default => null,
        };

        if (in_array($payload->source_type, ['weather', 'flight', 'flights', 'news'], true)) {
            $dispatched++;
        }
    }

    $this->info("Queued {$dispatched} normalization retry jobs.");

    return 0;
})->purpose('Retry normalization for pending raw provider payloads');

Artisan::command('indicators:build-airports', function (): int {
    BuildAirportIndicatorsJob::dispatch();

    $this->info('Dispatched airport indicator aggregation job.');

    return 0;
})->purpose('Queue airport indicator aggregation');

Artisan::command('indicators:build-cities', function (): int {
    BuildCityIndicatorsJob::dispatch();

    $this->info('Dispatched city indicator aggregation job.');

    return 0;
})->purpose('Queue city indicator aggregation');

Artisan::command('indicators:build-routes', function (): int {
    BuildRouteIndicatorsJob::dispatch();

    $this->info('Dispatched route indicator aggregation job.');

    return 0;
})->purpose('Queue route indicator aggregation');

Artisan::command('health:check-stale-data', function (StaleDataCheckService $staleDataCheckService): int {
    $report = $staleDataCheckService->inspectAndCache();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['status'] === 'ok' ? 0 : 1;
})->purpose('Inspect ingestion and indicator freshness');

Artisan::command('monitoring:refresh-alerts', function (OperationsMonitoringService $operationsMonitoringService): int {
    $alerts = $operationsMonitoringService->cacheFailureAlerts();

    $this->info('Cached '.count($alerts).' monitoring alert(s).');

    return 0;
})->purpose('Refresh cached monitoring alerts for failed jobs and stale providers');

Artisan::command('demo-data:clear-flights', function (): int {
    $seededPayloadIds = RawProviderPayload::query()
        ->where('source_type', 'flights')
        ->where('external_reference', 'like', 'seed:flight:%')
        ->pluck('id');

    $xxPayloadIds = FlightEvent::query()
        ->where('airline_code', 'XX')
        ->whereNotNull('raw_payload_id')
        ->pluck('raw_payload_id');

    $demoPayloadIds = $seededPayloadIds
        ->merge($xxPayloadIds)
        ->filter()
        ->unique()
        ->values();

    if ($demoPayloadIds->isEmpty()) {
        $this->info('No seeded demo flight payloads or XX carrier rows were found.');

        return 0;
    }

    $routeIds = FlightEvent::query()
        ->whereIn('raw_payload_id', $demoPayloadIds)
        ->orWhere('airline_code', 'XX')
        ->pluck('route_id')
        ->filter()
        ->unique()
        ->values();

    $deletedFlightEvents = FlightEvent::query()
        ->whereIn('raw_payload_id', $demoPayloadIds)
        ->orWhere('airline_code', 'XX')
        ->delete();

    $deletedRouteIndicators = $routeIds->isEmpty()
        ? 0
        : RouteIndicator::query()->whereIn('route_id', $routeIds)->delete();

    $deletedSnapshots = $routeIds->isEmpty()
        ? 0
        : RiskQuerySnapshot::query()->whereIn('route_id', $routeIds)->delete();

    $ingestionRunIds = RawProviderPayload::query()
        ->whereIn('id', $demoPayloadIds)
        ->pluck('ingestion_run_id')
        ->filter()
        ->unique()
        ->values();

    $deletedPayloads = RawProviderPayload::query()
        ->whereIn('id', $demoPayloadIds)
        ->delete();

    $deletedRuns = $ingestionRunIds->isEmpty()
        ? 0
        : \App\Models\IngestionRun::query()
            ->whereIn('id', $ingestionRunIds)
            ->where('source_type', 'flights')
            ->doesntHave('rawPayloads')
            ->delete();

    $this->info("Deleted {$deletedFlightEvents} demo or XX flight event(s).");
    $this->info("Deleted {$deletedPayloads} demo or XX raw payload(s).");
    $this->info("Deleted {$deletedRuns} empty ingestion run(s).");
    $this->info("Deleted {$deletedRouteIndicators} route indicator snapshot(s).");
    $this->info("Deleted {$deletedSnapshots} risk snapshot(s).");
    $this->line('Routes and watch targets were kept intact.');

    return 0;
})->purpose('Delete seeded demo flight data while keeping routes and watch targets');

// ── Per-destination fetch ─────────────────────────────────────────────────────

Artisan::command(
    'ingestion:fetch-for-destination {iata : Airport IATA code (e.g. CUN)} {--source=all : news|weather|flights|all}',
    function (): int {
        $iata   = strtoupper((string) $this->argument('iata'));
        $source = strtolower((string) $this->option('source'));

        // Validate airport exists
        $airport = Airport::query()->where('iata', $iata)->first();
        if (! $airport) {
            $this->error("Airport [{$iata}] not found in the database.");

            return 1;
        }

        // Count active watch targets for this destination
        $targetCount = WatchTarget::query()
            ->where('destination_airport_id', $airport->id)
            ->where('enabled', true)
            ->count();

        if ($targetCount === 0) {
            $this->warn("No enabled watch targets found for destination [{$iata}]. Dispatching global fetch anyway.");
        } else {
            $this->info("Found {$targetCount} active watch targets for {$iata}.");
        }

        // Dispatch the relevant jobs — the jobs pick up all enabled watch targets.
        // The criteria builder in FetchNewsDataJob now includes origin_iata/destination_iata
        // so the RSS provider will pull the right city feeds.
        $dispatched = [];

        if (in_array($source, ['all', 'news'], true)) {
            FetchNewsDataJob::dispatch();
            $dispatched[] = 'news';
        }

        if (in_array($source, ['all', 'weather'], true)) {
            FetchWeatherDataJob::dispatch();
            $dispatched[] = 'weather';
        }

        if (in_array($source, ['all', 'flights'], true)) {
            FetchFlightDataJob::dispatch();
            $dispatched[] = 'flights';
        }

        if (empty($dispatched)) {
            $this->error('Unknown --source value. Use: news, weather, flights, or all.');

            return 1;
        }

        $this->info('Dispatched ingestion jobs for [' . implode(', ', $dispatched) . '] → ' . $iata);

        return 0;
    }
)->purpose('Queue data ingestion jobs for a specific destination airport');

// ── Watch target management ───────────────────────────────────────────────────

Artisan::command(
    'watch-targets:list {--destination= : Filter by destination IATA} {--disabled : Show only disabled targets}',
    function (): int {
        $query = WatchTarget::query()
            ->with(['originAirport', 'originCity', 'destinationAirport', 'destinationCity'])
            ->orderByDesc('monitoring_priority')
            ->orderBy('id');

        if ($dest = $this->option('destination')) {
            $airport = Airport::query()->where('iata', strtoupper((string) $dest))->first();
            if ($airport) {
                $query->where('destination_airport_id', $airport->id);
            }
        }

        if ($this->option('disabled')) {
            $query->where('enabled', false);
        }

        $targets = $query->get();

        if ($targets->isEmpty()) {
            $this->info('No watch targets found.');

            return 0;
        }

        $this->table(
            ['ID', 'Origin', 'Destination', 'Priority', 'Window', 'Status'],
            $targets->map(fn (WatchTarget $t) => [
                $t->id,
                ($t->originAirport?->iata ?? '?') . ' (' . ($t->originCity?->name ?? '?') . ')',
                ($t->destinationAirport?->iata ?? '?') . ' (' . ($t->destinationCity?->name ?? '?') . ')',
                $t->monitoring_priority,
                $t->date_window_days . 'd',
                $t->enabled ? '✓ active' : '✗ paused',
            ])->all()
        );

        $enabled  = $targets->where('enabled', true)->count();
        $disabled = $targets->where('enabled', false)->count();
        $this->line("Total: {$targets->count()} targets ({$enabled} active, {$disabled} paused)");

        return 0;
    }
)->purpose('List all watch targets with their status');

Artisan::command(
    'watch-targets:toggle {id : Watch target ID} {--enable} {--disable}',
    function (): int {
        $id = (int) $this->argument('id');
        $wt = WatchTarget::query()->find($id);

        if (! $wt) {
            $this->error("Watch target [{$id}] not found.");

            return 1;
        }

        if ($this->option('enable')) {
            $wt->update(['enabled' => true]);
            $this->info("Watch target [{$id}] enabled.");
        } elseif ($this->option('disable')) {
            $wt->update(['enabled' => false]);
            $this->info("Watch target [{$id}] paused.");
        } else {
            $wt->update(['enabled' => ! $wt->enabled]);
            $this->info("Watch target [{$id}] toggled to: " . ($wt->fresh()?->enabled ? 'active' : 'paused'));
        }

        return 0;
    }
)->purpose('Enable or disable a specific watch target by ID');

Artisan::command(
    'watch-targets:set-priority {destination : Destination IATA} {priority : Integer 1-10}',
    function (): int {
        $iata     = strtoupper((string) $this->argument('destination'));
        $priority = max(1, min(10, (int) $this->argument('priority')));

        $airport = Airport::query()->where('iata', $iata)->first();
        if (! $airport) {
            $this->error("Airport [{$iata}] not found.");

            return 1;
        }

        $updated = WatchTarget::query()
            ->where('destination_airport_id', $airport->id)
            ->update(['monitoring_priority' => $priority]);

        $this->info("Updated priority to {$priority} for {$updated} watch target(s) → {$iata}.");

        return 0;
    }
)->purpose('Set monitoring priority for all watch targets pointing to a destination');

// ─────────────────────────────────────────────────────────────────────────────
// S C H E D U L E R
// ─────────────────────────────────────────────────────────────────────────────

Schedule::call(function (): void {
    Cache::forever(PlatformHealth::SCHEDULER_HEARTBEAT_CACHE_KEY, now()->toIso8601String());

    RecordQueueHeartbeat::dispatch();
})
    ->name('health:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

// ── Ingestion schedules (intervals driven by operations.schedule config) ────────

$flightsInterval = (int) config('operations.schedule.flights_interval_minutes', 15);
$weatherInterval = (int) config('operations.schedule.weather_interval_minutes', 30);
$newsInterval    = (int) config('operations.schedule.news_interval_minutes', 30);

Schedule::command('ingestion:fetch-flights')
    ->name('ingestion:flights')
    ->cron("*/{$flightsInterval} * * * *")
    ->withoutOverlapping();

Schedule::command('ingestion:fetch-weather')
    ->name('ingestion:weather')
    ->cron("*/{$weatherInterval} * * * *")
    ->withoutOverlapping();

Schedule::command('ingestion:fetch-news')
    ->name('ingestion:news')
    ->cron("*/{$newsInterval} * * * *")
    ->withoutOverlapping();

Schedule::command('ingestion:retry-normalization')
    ->name('normalization:retry')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('indicators:build-airports')
    ->name('indicators:airports')
    ->hourlyAt(5)
    ->withoutOverlapping();

Schedule::command('indicators:build-cities')
    ->name('indicators:cities')
    ->hourlyAt(10)
    ->withoutOverlapping();

Schedule::command('indicators:build-routes')
    ->name('indicators:routes')
    ->hourlyAt(15)
    ->withoutOverlapping();

Schedule::command('health:check-stale-data')
    ->name('health:stale-data-check')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('monitoring:refresh-alerts')
    ->name('monitoring:failure-alerts')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::job(new WarmPopularRoutesCacheJob())
    ->name('cache:warm-popular-routes')
    ->hourlyAt(20)
    ->withoutOverlapping();
