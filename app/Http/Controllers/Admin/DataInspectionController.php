<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FlightEvent;
use App\Models\WeatherEvent;
use App\Models\NewsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DataInspectionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'date' => ['nullable', 'date'],
        ]);

        $selectedCity = isset($validated['city_id'])
            ? City::query()->with(['country', 'airports'])->find($validated['city_id'])
            : null;
        $selectedDate = isset($validated['date'])
            ? Carbon::parse($validated['date'])->toDateString()
            : null;

        return view('admin.data-inspection.index', [
            'cities' => City::query()
                ->with('country')
                ->orderBy('name')
                ->get(),
            'selectedCity' => $selectedCity,
            'selectedDate' => $selectedDate,
            'weatherEvents' => $this->weatherEvents($selectedCity?->id, $selectedDate),
            'newsEvents' => $this->newsEvents($selectedCity?->id, $selectedDate),
            'flightEvents' => $this->flightEvents($selectedCity?->id, $selectedDate),
        ]);
    }

    private function weatherEvents(?int $cityId, ?string $date)
    {
        if (! $cityId || ! $date) {
            return collect();
        }

        return WeatherEvent::query()
            ->with(['airport.city', 'sourceProvider', 'rawPayload'])
            ->where('city_id', $cityId)
            ->whereDate('forecast_for', $date)
            ->orderBy('forecast_for')
            ->orderBy('event_time')
            ->get();
    }

    private function newsEvents(?int $cityId, ?string $date)
    {
        if (! $cityId || ! $date) {
            return collect();
        }

        return NewsEvent::query()
            ->with(['airport.city', 'sourceProvider', 'rawPayload'])
            ->where('city_id', $cityId)
            ->whereDate('published_at', $date)
            ->orderByDesc('published_at')
            ->get();
    }

    private function flightEvents(?int $cityId, ?string $date)
    {
        if (! $cityId || ! $date) {
            return collect();
        }

        return FlightEvent::query()
            ->with(['originAirport.city', 'destinationAirport.city', 'sourceProvider', 'rawPayload'])
            ->whereDate('travel_date', $date)
            ->whereHas('originAirport', fn ($query) => $query->where('city_id', $cityId))
            ->orderBy('travel_date')
            ->orderByDesc('disruption_score')
            ->get();
    }
}
