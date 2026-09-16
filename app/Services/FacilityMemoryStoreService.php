<?php

namespace App\Services;

use App\Models\FacilityMemory;
use App\Models\ReportLocation;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FacilityMemoryStoreService
{
    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request): array
    {
        $type = $request->string('type')->toString();

        if (! in_array($type, [FacilityMemory::TYPE_TEXT, FacilityMemory::TYPE_AUDIO], true)) {
            $type = FacilityMemory::TYPE_TEXT;
        }

        return $request->validate([
            'type' => ['required', Rule::in([FacilityMemory::TYPE_TEXT, FacilityMemory::TYPE_AUDIO])],
            'body' => [Rule::requiredIf($type === FacilityMemory::TYPE_TEXT), 'nullable', 'string', 'max:20000'],
            'audio' => [
                Rule::requiredIf($type === FacilityMemory::TYPE_AUDIO),
                'nullable',
                'file',
                'max:15360',
            ],
            'audio_duration_seconds' => ['nullable', 'integer', 'min:1', 'max:600'],
            'context_note' => ['nullable', 'string', 'max:2000'],
            'location_id' => ['nullable', 'exists:report_locations,id'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'era_text' => ['nullable', 'string', 'max:120'],
            'visitor_name' => ['nullable', 'string', 'max:120'],
            'tour_id' => ['nullable', 'integer', 'exists:tours,id'],
            'consent_given' => ['accepted'],
        ], [
            'body.required' => 'Skriv berättelsen innan du sparar.',
            'audio.required' => 'Spela in eller välj en ljudfil innan du sparar.',
            'audio.max' => 'Ljudfilen får vara högst 15 MB.',
            'consent_given.accepted' => 'Samtycke krävs för att spara minnet.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function store(array $validated, Request $request, int $collectedByUserId, bool $restrictTourToGuide = false): FacilityMemory
    {
        $type = (string) $validated['type'];
        $tourId = isset($validated['tour_id']) ? (int) $validated['tour_id'] : null;

        if ($tourId !== null) {
            $tour = Tour::query()->findOrFail($tourId);

            if ($restrictTourToGuide && (int) $tour->guide_id !== $collectedByUserId) {
                throw ValidationException::withMessages([
                    'tour_id' => 'Du kan bara koppla minnet till en tur du själv guidar.',
                ]);
            }
        }

        $locationText = $validated['location_text'] ?? null;

        if ($locationText === null && ! empty($validated['location_id'])) {
            $locationText = ReportLocation::query()
                ->whereKey((int) $validated['location_id'])
                ->value('name');
        }

        $memoryData = [
            'type' => $type,
            'body' => $type === FacilityMemory::TYPE_TEXT ? ($validated['body'] ?? null) : null,
            'context_note' => $validated['context_note'] ?? null,
            'location_text' => $locationText,
            'era_text' => $validated['era_text'] ?? null,
            'visitor_name' => $validated['visitor_name'] ?? null,
            'consent_type' => $type === FacilityMemory::TYPE_AUDIO
                ? FacilityMemory::CONSENT_RECORDED
                : FacilityMemory::CONSENT_WRITTEN,
            'consent_given' => true,
            'tour_id' => $tourId,
            'collected_by' => $collectedByUserId,
            'status' => FacilityMemory::STATUS_SUBMITTED,
        ];

        if ($type === FacilityMemory::TYPE_AUDIO) {
            $uploaded = $request->file('audio');

            if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
                throw ValidationException::withMessages([
                    'audio' => 'Ljudfilen kunde inte läsas.',
                ]);
            }

            $storedPath = $uploaded->store('facility_memories/'.now()->format('Y/m'), 'public');

            $memoryData['audio_path'] = $storedPath;
            $memoryData['audio_duration_seconds'] = isset($validated['audio_duration_seconds'])
                ? (int) $validated['audio_duration_seconds']
                : null;
            $memoryData['audio_mime_type'] = $uploaded->getClientMimeType();
            $memoryData['audio_size'] = $uploaded->getSize();
        }

        return FacilityMemory::query()->create($memoryData);
    }
}
