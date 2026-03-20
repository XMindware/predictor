<?php

namespace App\Http\Controllers;

use App\Support\PlatformHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'redis' => $this->redisCheck(),
            'migrations' => $this->migrationCheck(),
            'queue' => $this->heartbeatCheck(
                PlatformHealth::QUEUE_WORKER_HEARTBEAT_CACHE_KEY,
                (int) env('QUEUE_HEALTH_MAX_AGE', 180),
                [
                    'connection' => config('queue.default'),
                    'queue' => config('queue.connections.redis.queue'),
                ],
            ),
            'scheduler' => $this->heartbeatCheck(
                PlatformHealth::SCHEDULER_HEARTBEAT_CACHE_KEY,
                (int) env('SCHEDULER_HEALTH_MAX_AGE', 180),
                [
                    'frequency' => 'every_minute',
                ],
            ),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Check the primary database connection.
     *
     * @return array<string, mixed>
     */
    private function databaseCheck(): array
    {
        try {
            DB::connection()->select('select 1');

            return [
                'status' => 'ok',
                'connection' => config('database.default'),
                'database' => config('database.connections.'.config('database.default').'.database'),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'connection' => config('database.default'),
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Check the Redis connection.
     *
     * @return array<string, mixed>
     */
    private function redisCheck(): array
    {
        try {
            $connection = config('queue.connections.redis.connection', 'default');
            $response = Redis::connection($connection)->ping();

            return [
                'status' => in_array((string) $response, ['1', '+PONG', 'PONG'], true) ? 'ok' : 'error',
                'client' => config('database.redis.client'),
                'connection' => $connection,
                'host' => config('database.redis.default.host'),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'client' => config('database.redis.client'),
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Check migration state.
     *
     * @return array<string, mixed>
     */
    private function migrationCheck(): array
    {
        try {
            $table = config('database.migrations.table', 'migrations');

            if (! Schema::hasTable($table)) {
                return [
                    'status' => 'error',
                    'message' => 'Migration repository is missing.',
                ];
            }

            $files = collect(glob(database_path('migrations/*.php')) ?: [])
                ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => $path])
                ->all();
            $ran = DB::table($table)->pluck('migration')->all();
            $pending = array_values(array_diff(array_keys($files), $ran));

            return [
                'status' => $pending === [] ? 'ok' : 'error',
                'ran' => count($ran),
                'pending' => count($pending),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Check the freshness of a cached heartbeat.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function heartbeatCheck(string $key, int $maxAgeInSeconds, array $meta = []): array
    {
        try {
            $value = Cache::get($key);

            if (! is_string($value) || $value === '') {
                return [
                    'status' => 'error',
                    'last_seen' => null,
                    'max_age_seconds' => $maxAgeInSeconds,
                    'message' => 'No heartbeat recorded yet.',
                    ...$meta,
                ];
            }

            $lastSeen = Carbon::parse($value);
            $age = $lastSeen->diffInSeconds(now());

            return [
                'status' => $age <= $maxAgeInSeconds ? 'ok' : 'error',
                'last_seen' => $lastSeen->toIso8601String(),
                'age_seconds' => $age,
                'max_age_seconds' => $maxAgeInSeconds,
                ...$meta,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'last_seen' => null,
                'max_age_seconds' => $maxAgeInSeconds,
                'message' => $exception->getMessage(),
                ...$meta,
            ];
        }
    }
}
