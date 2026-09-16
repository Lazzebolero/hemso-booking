<?php

namespace App\Http\Requests;

use App\Models\VisitorDog;
use App\Support\VisitorDogCareFlags;
use Illuminate\Foundation\Http\FormRequest;

class StoreDashboardVisitorDogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createFromDashboard', VisitorDog::class) === true;
    }

    protected function prepareForValidation(): void
    {
        VisitorDogCareFlags::mergeNormalizedIntoRequest($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dog_name' => ['required', 'string', 'max:120'],
            'breed' => ['nullable', 'string', 'max:120'],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'tour_start_time' => ['nullable', 'date_format:H:i'],
            'visit_date' => ['nullable', 'date'],
            ...VisitorDogCareFlags::validationRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dog_name.required' => 'Ange hundens namn.',
        ];
    }
}
