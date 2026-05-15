<?php

namespace App\Http\Requests;

use App\Models\VisitorDog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreVisitorDogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', VisitorDog::class) === true;
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
            'visit_date' => ['required', 'date'],
            'tour_start_time' => ['nullable', 'date_format:H:i'],
            'photo' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                    ->max(10240),
            ],
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
        ];
    }
}
