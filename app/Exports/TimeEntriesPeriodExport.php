<?php

namespace App\Exports;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TimeEntriesPeriodExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly ?int $userId = null,
        private readonly ?string $status = null,
    ) {}

    public function sheets(): array
    {
        $entries = TimeEntry::query()
            ->with('user')
            ->whereBetween('work_date', [$this->from->toDateString(), $this->to->toDateString()])
            ->when($this->userId !== null, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->status !== null && $this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy('user_id')
            ->orderBy('work_date')
            ->orderBy('start_at')
            ->get();

        return [
            new TimeEntriesDetailSheet($entries),
            new TimeEntriesSummarySheet($entries),
        ];
    }
}

class TimeEntriesDetailSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $entries) {}

    public function title(): string
    {
        return 'Alla pass';
    }

    public function headings(): array
    {
        return [
            'Namn',
            'Roll',
            'Datum',
            'Original in',
            'Original ut',
            'Rapporterad start',
            'Rapporterad slut',
            'Rast minuter',
            'Arbetad tid',
            'Arbetade minuter',
            'Status',
            'Personal kommentar',
            'Admin kommentar',
        ];
    }

    public function collection(): Collection
    {
        return $this->entries->map(function (TimeEntry $entry) {
            return [
                $entry->user->name ?? '',
                $entry->user->role ?? '',
                optional($entry->work_date)->format('Y-m-d'),
                optional($entry->clock_in_at_original)->format('Y-m-d H:i'),
                optional($entry->clock_out_at_original)->format('Y-m-d H:i'),
                optional($entry->start_at)->format('Y-m-d H:i'),
                optional($entry->end_at)->format('Y-m-d H:i'),
                (int) $entry->break_minutes,
                $entry->worked_hours_formatted,
                $entry->worked_minutes,
                $entry->status_label,
                $entry->user_comment,
                $entry->admin_comment,
            ];
        });
    }
}

class TimeEntriesSummarySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly Collection $entries) {}

    public function title(): string
    {
        return 'Summering';
    }

    public function headings(): array
    {
        return [
            'Namn',
            'Roll',
            'Antal pass',
            'Totala minuter',
            'Total tid',
            'Öppna',
            'Utkast',
            'Inskickade',
            'Korrigerade',
            'Godkända',
        ];
    }

    public function collection(): Collection
    {
        return $this->entries
            ->groupBy('user_id')
            ->map(function (Collection $entries) {
                $first = $entries->first();
                $minutes = $entries->sum(fn (TimeEntry $entry) => $entry->worked_minutes);

                return [
                    $first->user->name ?? '',
                    $first->user->role ?? '',
                    $entries->count(),
                    $minutes,
                    sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60),
                    $entries->where('status', TimeEntry::STATUS_OPEN)->count(),
                    $entries->where('status', TimeEntry::STATUS_DRAFT)->count(),
                    $entries->where('status', TimeEntry::STATUS_SUBMITTED)->count(),
                    $entries->where('status', TimeEntry::STATUS_CORRECTED)->count(),
                    $entries->where('status', TimeEntry::STATUS_APPROVED)->count(),
                ];
            })
            ->values();
    }
}
