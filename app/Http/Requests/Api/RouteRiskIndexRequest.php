<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class RouteRiskIndexRequest extends FormRequest
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
            'destination' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('operations.v1_route_risk_limit', 10)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! filled($this->input('date'))) {
                    return;
                }

                $travelDate = Carbon::parse((string) $this->input('date'))->startOfDay();
                $earliestDate = now()->startOfDay();
                $latestDate = now()
                    ->addHours((int) config('operations.v1_risk_window_hours', 72))
                    ->endOfDay();

                if ($travelDate->lt($earliestDate) || $travelDate->gt($latestDate)) {
                    $validator->errors()->add(
                        'date',
                        sprintf(
                            'V1 route risk only supports travel dates between %s and %s.',
                            $earliestDate->toDateString(),
                            $latestDate->toDateString()
                        )
                    );
                }
            },
        ];
    }
}
