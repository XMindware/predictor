<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class RiskAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_airport' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_airport' => ['nullable', 'string', 'max:255'],
            'travel_date' => ['required', 'date'],
            'airline_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (blank($this->input('origin_city')) && blank($this->input('origin_airport'))) {
                    $validator->errors()->add('origin', 'An origin city or origin airport is required.');
                }

                if (blank($this->input('destination_city')) && blank($this->input('destination_airport'))) {
                    $validator->errors()->add('destination', 'A destination city or destination airport is required.');
                }

                if (filled($this->input('travel_date'))) {
                    $travelDate = Carbon::parse((string) $this->input('travel_date'))->startOfDay();
                    $earliestDate = now()->startOfDay();
                    $latestDate = now()
                        ->addHours((int) config('operations.v1_risk_window_hours', 72))
                        ->endOfDay();

                    if ($travelDate->lt($earliestDate) || $travelDate->gt($latestDate)) {
                        $validator->errors()->add(
                            'travel_date',
                            sprintf(
                                'V1 risk assessment only supports travel dates between %s and %s.',
                                $earliestDate->toDateString(),
                                $latestDate->toDateString()
                            )
                        );
                    }
                }
            },
        ];
    }
}
