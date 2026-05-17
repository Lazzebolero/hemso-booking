<?php

namespace App\Support;

use App\Models\VisitorDog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitorDogSupport
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dateRangeFromRequest(Request $request): array
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $from = $request->filled('from_date')
            ? Carbon::parse($request->string('from_date'))->startOfDay()
            : now()->startOfDay();

        $to = $request->filled('to_date')
            ? Carbon::parse($request->string('to_date'))->startOfDay()
            : now()->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    public const RETURN_INDEX = 'index';

    public const RETURN_GALLERY = 'gallery';

    public const RETURN_MINE = 'mine';

    /**
     * @return array<string, string>
     */
    public static function dateFilterQueryFromRequest(Request $request): array
    {
        return array_filter([
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ], static fn ($value): bool => is_string($value) && $value !== '');
    }

    /**
     * @return array<string, string>
     */
    public static function linkQueryForReturn(Request $request, string $returnTo): array
    {
        return array_merge(
            self::dateFilterQueryFromRequest($request),
            ['return' => $returnTo],
        );
    }

    /**
     * @return array<string, string>
     */
    public static function preserveNavigationQuery(Request $request): array
    {
        $query = self::dateFilterQueryFromRequest($request);

        $returnTo = $request->input('return');
        if (is_string($returnTo) && in_array($returnTo, [self::RETURN_INDEX, self::RETURN_GALLERY, self::RETURN_MINE], true)) {
            $query['return'] = $returnTo;
        }

        return $query;
    }

    /**
     * @return array{url: string, label: string, filterQuery: array<string, string>, returnTo: string}
     */
    public static function backNavigation(Request $request, ?string $routePrefix = null): array
    {
        $filterQuery = self::dateFilterQueryFromRequest($request);
        $returnTo = $request->string('return')->toString();

        if ($returnTo === self::RETURN_GALLERY && is_string($routePrefix) && $routePrefix !== '') {
            return [
                'url' => route($routePrefix.'.visitor-dogs.gallery', $filterQuery),
                'label' => 'Till galleriet',
                'filterQuery' => $filterQuery,
                'returnTo' => self::RETURN_GALLERY,
            ];
        }

        if ($returnTo === self::RETURN_MINE || $routePrefix === null) {
            return [
                'url' => route('visitor-dogs.index', $filterQuery),
                'label' => 'Mina hundar',
                'filterQuery' => $filterQuery,
                'returnTo' => self::RETURN_MINE,
            ];
        }

        return [
            'url' => route($routePrefix.'.visitor-dogs.index', $filterQuery),
            'label' => 'Till listan',
            'filterQuery' => $filterQuery,
            'returnTo' => self::RETURN_INDEX,
        ];
    }

    public static function routeForDog(string $routeName, VisitorDog $dog, array $query = []): string
    {
        return route($routeName, array_merge(['visitorDog' => $dog], $query));
    }

    public static function storeUploadedPhoto(?UploadedFile $uploaded): ?string
    {
        if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
            return null;
        }

        $stored = $uploaded->store(
            'visitor_dogs/'.now()->format('Y/m'),
            'public'
        );

        return $stored !== false ? $stored : null;
    }

    public static function uploadedPhotoFromRequest(Request $request): ?UploadedFile
    {
        $uploaded = $request->file('photo')
            ?? $request->file('photo_camera')
            ?? $request->file('photo_library');

        return $uploaded instanceof UploadedFile ? $uploaded : null;
    }

    public static function streamPhoto(VisitorDog $visitorDog): BinaryFileResponse
    {
        if (empty($visitorDog->photo_path)) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($visitorDog->photo_path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($visitorDog->photo_path);

        if (! is_file($absolutePath)) {
            abort(404);
        }

        return response()->file($absolutePath);
    }
}
