<?php

namespace App\Http\Requests;

use App\Models\LockedPayrollPeriod;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreLockedPayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = Carbon::parse($this->input('start_date'))->toDateString();
            $end = Carbon::parse($this->input('end_date'))->toDateString();

            $overlap = LockedPayrollPeriod::query()
                ->where('start_date', '<=', $end)
                ->where('end_date', '>=', $start)
                ->exists();

            if ($overlap) {
                $validator->errors()->add('start_date', 'Intervallet överlappar en befintlig låsning.');
            }

            $inclusiveDays = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
            if ($inclusiveDays > 400) {
                $validator->errors()->add('end_date', 'Ett låsintervall får vara högst 400 dagar.');
            }
        });
    }
}
