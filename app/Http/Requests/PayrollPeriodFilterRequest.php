<?php

namespace App\Http\Requests;

use App\Models\TimeEntry;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PayrollPeriodFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed|string>>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'nullable', 'string', Rule::in(['current', 'previous', 'custom'])],
            'from' => ['nullable', 'date', 'required_if:period,custom'],
            'to' => ['nullable', 'date', 'required_if:period,custom'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in([
                TimeEntry::STATUS_OPEN,
                TimeEntry::STATUS_DRAFT,
                TimeEntry::STATUS_SUBMITTED,
                TimeEntry::STATUS_CORRECTED,
                TimeEntry::STATUS_APPROVED,
            ])],
            'view' => ['sometimes', 'nullable', 'string', Rule::in([
                'problems',
                'open',
                'unapproved',
                'deviations',
                'submitted',
                'corrected',
                'all',
            ])],
            'ack' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('period')) {
            $this->merge(['period' => 'current']);
        }

        if ($this->input('user_id') === '' || $this->input('user_id') === null) {
            $this->merge(['user_id' => null]);
        }

        if ($this->input('status') === '') {
            $this->merge(['status' => null]);
        }
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('period') !== 'custom') {
                return;
            }

            $fromRaw = $this->input('from');
            $toRaw = $this->input('to');

            if (! is_string($fromRaw) || ! is_string($toRaw) || $fromRaw === '' || $toRaw === '') {
                return;
            }

            try {
                $from = Carbon::parse($fromRaw)->startOfDay();
                $to = Carbon::parse($toRaw)->startOfDay();
            } catch (\Throwable) {
                return;
            }

            if ($from->gt($to)) {
                $validator->errors()->add('from', 'Från-datum måste vara före eller samma som till-datum.');
            }

            $inclusiveDays = $from->diffInDays($to) + 1;
            if ($inclusiveDays > 400) {
                $validator->errors()->add('to', 'En anpassad period får vara högst 400 dagar.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $query = $this->query();

        $target = str_contains($this->path(), 'control-panel')
            ? route('admin.time.control-panel', $query)
            : route('admin.time.index', $query);

        throw new HttpResponseException(
            redirect()->to($target)->withErrors($validator)->withInput()
        );
    }

    /**
     * @return array{period: string, from: ?string, to: ?string}
     */
    public function payrollPeriodQuery(): array
    {
        /** @var array{period?: string, from?: string|null, to?: string|null} $validated */
        $validated = $this->validated();

        return [
            'period' => $validated['period'] ?? 'current',
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }
}
