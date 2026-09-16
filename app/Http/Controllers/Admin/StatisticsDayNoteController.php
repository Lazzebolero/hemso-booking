<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StatisticsDayNote;
use App\Support\ActiveRole;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StatisticsDayNoteController extends Controller
{
    public function edit(Request $request): View
    {
        $this->ensureTableExists();

        $date = $this->resolveDate($request->query('date'));

        $note = StatisticsDayNote::query()
            ->with('updatedBy')
            ->whereDate('note_date', $date)
            ->first();

        return view('admin.statistics-notes.edit', [
            'date' => $date,
            'note' => $note,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureTableExists();

        $data = $request->validate([
            'date' => ['required', 'date'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $date = $this->resolveDate($data['date']);
        $body = trim((string) ($data['body'] ?? ''));

        $note = StatisticsDayNote::query()
            ->whereDate('note_date', $date->toDateString())
            ->first() ?? new StatisticsDayNote([
                'note_date' => $date->toDateString(),
            ]);

        if ($body === '') {
            if ($note->exists) {
                $note->delete();
            }

            return redirect()
                ->route(ActiveRole::routePrefix().'.statistics-notes.edit', [
                    'date' => $date->toDateString(),
                ])
                ->with('success', 'Statistiknotering raderad.');
        }

        if (! $note->exists) {
            $note->created_by = auth()->id();
        }

        $note->body = $body;
        $note->updated_by = auth()->id();
        $note->save();

        return redirect()
            ->route(ActiveRole::routePrefix().'.statistics-notes.edit', [
                'date' => $date->toDateString(),
            ])
            ->with('success', 'Statistiknotering sparad.');
    }

    private function resolveDate(mixed $date): Carbon
    {
        try {
            return Carbon::parse((string) ($date ?: now()->toDateString()))->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function ensureTableExists(): void
    {
        if (! Schema::hasTable('statistics_day_notes')) {
            throw ValidationException::withMessages([
                'date' => 'Tabellen för statistiknoteringar saknas. Kör php artisan migrate.',
            ]);
        }
    }
}
