<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionPerson;
use App\Services\ProductionPresenceService;
use App\Support\BergPaths;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class ProductionPresenceController extends Controller
{
    public function __construct(
        private ProductionPresenceService $presence,
    ) {}

    public function show(Request $request): View|Response
    {
        try {
            $html = view('berg.presence', $this->boardData($request))->render();

            return response($html)->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        } catch (Throwable $exception) {
            report($exception);

            return response($this->fallbackHtml($exception));
        }
    }

    public function poll(Request $request): JsonResponse
    {
        $data = $this->boardData($request);

        return response()->json([
            'html' => view('berg.partials.live', $data)->render(),
            'insideCount' => $data['insideCount'] ?? 0,
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    public function stamp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'with_group' => ['nullable', 'boolean'],
        ]);

        [$production, $actor] = $this->requireActor($request);

        try {
            $count = $this->presence->stampSelf(
                $actor,
                $data['direction'],
                $request->user(),
                (bool) ($data['with_group'] ?? false),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['presence' => $exception->getMessage()]);
        }

        $message = $data['direction'] === ProductionPerson::DIRECTION_IN
            ? 'Stämplad in i berget.'
            : 'Stämplad ut ur berget.';

        if ((bool) ($data['with_group'] ?? false)) {
            $message .= ' '.$count.' personer flyttades.';
        }

        return redirect('/berget')->with('success', $message);
    }

    public function stampPerson(Request $request, ProductionPerson $person): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
        ]);

        [$production] = $this->requireActor($request);

        if ((int) $person->production_id !== (int) $production->id) {
            abort(404);
        }

        try {
            $this->presence->stampPerson($person, $data['direction'], $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['presence' => $exception->getMessage()]);
        }

        return redirect('/berget')->with('success', $person->name.' uppdaterad.');
    }

    public function markDeparted(Request $request, ProductionPerson $person): RedirectResponse
    {
        [$production] = $this->requireActor($request);

        if ((int) $person->production_id !== (int) $production->id) {
            abort(404);
        }

        try {
            $this->presence->markDeparted($person, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['presence' => $exception->getMessage()]);
        }

        return back()->with('success', $person->name.' är märkt som utrest och tas inte med i gruppstämpel.');
    }

    public function restoreDeparted(Request $request, ProductionPerson $person): RedirectResponse
    {
        if (session('active_role') !== Roles::PRODUKTION_ADMIN && session('active_role') !== Roles::ADMIN) {
            abort(403, 'Bara admin kan återställa utresta deltagare.');
        }

        [$production] = $this->requireActor($request);

        if ((int) $person->production_id !== (int) $production->id) {
            abort(404);
        }

        try {
            $this->presence->restoreDeparted($person);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['presence' => $exception->getMessage()]);
        }

        return back()->with('success', $person->name.' är tillbaka bland deltagarna.');
    }

    /**
     * @return array<string, mixed>
     */
    private function boardData(Request $request): array
    {
        $defaults = [
            'production' => null,
            'actor' => null,
            'insideCount' => 0,
            'remainingParticipantCount' => 0,
            'remainingParticipantsInside' => 0,
            'groupStampCount' => 0,
            'crewInside' => collect(),
            'crewOutside' => collect(),
            'participantsInside' => collect(),
            'participantsOutside' => collect(),
            'departedParticipants' => collect(),
            'canManagePeople' => session('active_role') === Roles::PRODUKTION_ADMIN,
            'pollUrl' => url(BergPaths::poll()),
            'unavailableMessage' => null,
        ];

        try {
            $production = $this->presence->currentProduction();
            $actor = $production
                ? $this->presence->personForUser($production, $request->user())
                : null;

            if ($production === null || $actor === null) {
                $defaults['production'] = $production;
                $defaults['actor'] = $actor;
                $defaults['unavailableMessage'] = $this->unavailableMessage($production, $actor);

                return $defaults;
            }

            $board = $this->presence->board($production, $actor);

            return array_merge($defaults, $board, [
                'unavailableMessage' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $defaults['unavailableMessage'] = $this->failureMessage($exception);

            return $defaults;
        }
    }

    /**
     * @return array{0: Production, 1: ProductionPerson}
     */
    private function requireActor(Request $request): array
    {
        $production = $this->presence->currentProduction();

        if ($production === null) {
            abort(404, 'Ingen aktiv produktion just nu.');
        }

        $actor = $this->presence->personForUser($production, $request->user());

        if ($actor === null) {
            abort(403, 'Du finns inte med på personlistan för den här produktionen.');
        }

        return [$production, $actor];
    }

    private function unavailableMessage(?Production $production, ?ProductionPerson $actor): ?string
    {
        if ($production === null) {
            return 'Ingen aktiv produktion just nu. Be Hemsö-admin att skapa den under Projekt.';
        }

        if ($actor === null) {
            return 'Du finns inte med på personlistan för den här produktionen.';
        }

        return null;
    }

    private function failureMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Route [') || str_contains($message, 'not defined')) {
            return 'En rutt till /berget saknas. Ladda upp routes/production.php och rensa route-cache.';
        }

        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'Base table or view not found')) {
            return 'Databasen saknar en tabell för närvaro. php artisan migrate behöver köras på servern.';
        }

        if (str_contains($message, 'Failed to open stream') || str_contains($message, 'not exist')) {
            return 'En vyfil för /berget saknas på servern.';
        }

        return 'Närvaro i berget kunde inte visas. Titta i storage/logs/laravel.log.';
    }

    private function fallbackHtml(Throwable $exception): string
    {
        $text = e($this->failureMessage($exception));
        $token = e(csrf_token());

        return <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Närvaro i berget</title>
</head>
<body style="font-family:sans-serif;background:#0b1220;color:#f8fafc;padding:24px;">
    <h1>Närvaro i berget</h1>
    <p>{$text}</p>
    <p><a href="/berget" style="color:#93c5fd;">Försök igen</a></p>
    <form method="POST" action="/logout">
        <input type="hidden" name="_token" value="{$token}">
        <button type="submit">Logga ut</button>
    </form>
</body>
</html>
HTML;
    }
}
