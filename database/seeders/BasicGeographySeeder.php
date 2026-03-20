<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

class BasicGeographySeeder extends Seeder
{
    /**
     * Seed baseline countries, cities, and airports used by the application.
     */
    public function run(): void
    {
        $countries = collect([
            'Mexico' => [
                'Cancun' => [
                    [
                        'name' => 'Cancun International Airport',
                        'iata' => 'CUN',
                        'icao' => 'MMUN',
                        'timezone' => 'America/Cancun',
                        'latitude' => 21.0365,
                        'longitude' => -86.8771,
                    ],
                ],
                'Merida' => [
                    [
                        'name' => 'Merida International Airport',
                        'iata' => 'MID',
                        'icao' => 'MMMD',
                        'timezone' => 'America/Merida',
                        'latitude' => 20.9370,
                        'longitude' => -89.6577,
                    ],
                ],
                'Guadalajara' => [
                    [
                        'name' => 'Guadalajara International Airport',
                        'iata' => 'GDL',
                        'icao' => 'MMGL',
                        'timezone' => 'America/Mexico_City',
                        'latitude' => 20.5218,
                        'longitude' => -103.3112,
                    ],
                ],
                'Mexico City' => [
                    [
                        'name' => 'Mexico City International Airport',
                        'iata' => 'MEX',
                        'icao' => 'MMMX',
                        'timezone' => 'America/Mexico_City',
                        'latitude' => 19.4361,
                        'longitude' => -99.0719,
                    ],
                ],
                'Monterrey' => [
                    [
                        'name' => 'Monterrey International Airport',
                        'iata' => 'MTY',
                        'icao' => 'MMMY',
                        'timezone' => 'America/Monterrey',
                        'latitude' => 25.7785,
                        'longitude' => -100.1070,
                    ],
                ],
                'Puerto Vallarta' => [
                    [
                        'name' => 'Puerto Vallarta International Airport',
                        'iata' => 'PVR',
                        'icao' => 'MMPR',
                        'timezone' => 'America/Mexico_City',
                        'latitude' => 20.6801,
                        'longitude' => -105.2541,
                    ],
                ],
                'San Jose del Cabo' => [
                    [
                        'name' => 'Los Cabos International Airport',
                        'iata' => 'SJD',
                        'icao' => 'MMSD',
                        'timezone' => 'America/Mazatlan',
                        'latitude' => 23.1518,
                        'longitude' => -109.7210,
                    ],
                ],
                'Tijuana' => [
                    [
                        'name' => 'Tijuana International Airport',
                        'iata' => 'TIJ',
                        'icao' => 'MMTJ',
                        'timezone' => 'America/Tijuana',
                        'latitude' => 32.5411,
                        'longitude' => -116.9701,
                    ],
                ],
            ],
            'United States' => [
                'Atlanta' => [
                    [
                        'name' => 'Hartsfield-Jackson Atlanta International Airport',
                        'iata' => 'ATL',
                        'icao' => 'KATL',
                        'timezone' => 'America/New_York',
                        'latitude' => 33.6407,
                        'longitude' => -84.4277,
                    ],
                ],
                'Chicago' => [
                    [
                        'name' => "O'Hare International Airport",
                        'iata' => 'ORD',
                        'icao' => 'KORD',
                        'timezone' => 'America/Chicago',
                        'latitude' => 41.9742,
                        'longitude' => -87.9073,
                    ],
                ],
                'Dallas' => [
                    [
                        'name' => 'Dallas/Fort Worth International Airport',
                        'iata' => 'DFW',
                        'icao' => 'KDFW',
                        'timezone' => 'America/Chicago',
                        'latitude' => 32.8998,
                        'longitude' => -97.0403,
                    ],
                ],
                'Houston' => [
                    [
                        'name' => 'George Bush Intercontinental Airport',
                        'iata' => 'IAH',
                        'icao' => 'KIAH',
                        'timezone' => 'America/Chicago',
                        'latitude' => 29.9902,
                        'longitude' => -95.3368,
                    ],
                ],
                'Los Angeles' => [
                    [
                        'name' => 'Los Angeles International Airport',
                        'iata' => 'LAX',
                        'icao' => 'KLAX',
                        'timezone' => 'America/Los_Angeles',
                        'latitude' => 33.9416,
                        'longitude' => -118.4085,
                    ],
                ],
                'Miami' => [
                    [
                        'name' => 'Miami International Airport',
                        'iata' => 'MIA',
                        'icao' => 'KMIA',
                        'timezone' => 'America/New_York',
                        'latitude' => 25.7959,
                        'longitude' => -80.2870,
                    ],
                ],
                'New York' => [
                    [
                        'name' => 'John F. Kennedy International Airport',
                        'iata' => 'JFK',
                        'icao' => 'KJFK',
                        'timezone' => 'America/New_York',
                        'latitude' => 40.6413,
                        'longitude' => -73.7781,
                    ],
                ],
                'San Francisco' => [
                    [
                        'name' => 'San Francisco International Airport',
                        'iata' => 'SFO',
                        'icao' => 'KSFO',
                        'timezone' => 'America/Los_Angeles',
                        'latitude' => 37.6213,
                        'longitude' => -122.3790,
                    ],
                ],
            ],
            'Canada' => [
                'Calgary' => [
                    [
                        'name' => 'Calgary International Airport',
                        'iata' => 'YYC',
                        'icao' => 'CYYC',
                        'timezone' => 'America/Edmonton',
                        'latitude' => 51.1315,
                        'longitude' => -114.0106,
                    ],
                ],
                'Montreal' => [
                    [
                        'name' => 'Montreal-Trudeau International Airport',
                        'iata' => 'YUL',
                        'icao' => 'CYUL',
                        'timezone' => 'America/Toronto',
                        'latitude' => 45.4706,
                        'longitude' => -73.7408,
                    ],
                ],
                'Toronto' => [
                    [
                        'name' => 'Toronto Pearson International Airport',
                        'iata' => 'YYZ',
                        'icao' => 'CYYZ',
                        'timezone' => 'America/Toronto',
                        'latitude' => 43.6777,
                        'longitude' => -79.6248,
                    ],
                ],
                'Vancouver' => [
                    [
                        'name' => 'Vancouver International Airport',
                        'iata' => 'YVR',
                        'icao' => 'CYVR',
                        'timezone' => 'America/Vancouver',
                        'latitude' => 49.1967,
                        'longitude' => -123.1815,
                    ],
                ],
            ],
        ]);

        $countries->each(function (array $cities, string $countryName): void {
            $country = Country::query()->updateOrCreate(
                ['name' => $countryName],
                ['name' => $countryName],
            );

            collect($cities)->each(function (array $airports, string $cityName) use ($country): void {
                $city = City::query()->updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $cityName,
                    ],
                    [
                        'country_id' => $country->id,
                        'name' => $cityName,
                    ],
                );

                collect($airports)->each(function (array $airport) use ($country, $city): void {
                    Airport::query()->updateOrCreate(
                        ['iata' => $airport['iata']],
                        array_merge($airport, [
                            'country_id' => $country->id,
                            'city_id' => $city->id,
                        ]),
                    );
                });
            });
        });
    }
}
