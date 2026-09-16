<?php

namespace App\Http\Requests;

use App\Models\VisitorDog;
use App\Support\VisitorDogCareFlags;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateVisitorDogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visitorDog = $this->route('visitorDog');

        return $visitorDog instanceof VisitorDog
            && $this->user()?->can('update', $visitorDog) === true;
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
        if ($this->photoCompletionOnly()) {
            return [
                'photo' => [
                    'required',
                    File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                        ->max(10240),
                ],
                ...VisitorDogCareFlags::validationRules(),
            ];
        }

        return [
            'dog_name' => ['required', 'string', 'max:120'],
            'breed' => ['nullable', 'string', 'max:120'],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'visit_date' => ['required', 'date'],
            'tour_start_time' => ['nullable', 'date_format:H:i'],
            'photo' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                    ->max(10240),
            ],
            'remove_photo' => ['sometimes', 'boolean'],
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
            'visit_date.required' => 'Ange datum.',
            'photo.max' => 'Bilden får vara högst 10 MB.',
            'photo.required' => 'Ladda upp en bild för att komplettera rapporten.',
        ];
    }

    public function photoCompletionOnly(): bool
    {
        $visitorDog = $this->route('visitorDog');

        return $visitorDog instanceof VisitorDog
            && $this->user()?->can('completePhoto', $visitorDog) === true;
    }
}
