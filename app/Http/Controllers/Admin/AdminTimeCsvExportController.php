<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Services\PayrollPeriodService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTimeCsvExportController extends Controller
{
    public function entries(Request $request): StreamedResponse
    {
        $period = PayrollPeriodService::resolveFromRequest($request->all());

        $entries = TimeEntry::query()
            ->with('user')
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('work_date')
            ->orderBy('user_id')
            ->orderBy('start_at')
            ->get();

        return $this->downloadCsv('tidrapport_pass_' . $period['file_label'] . '.csv', function ($handle) use ($entries) {
            $this->putRow($handle, [
                'Namn', 'Roll', 'Datum', 'Original in', 'Original ut',
                'Rapporterad start', 'Rapporterad slut', 'Rast minuter',
                'Arbetad tid', 'Arbetade minuter', 'Status',
                'Personal kommentar', 'Admin kommentar',
            ]);

            foreach ($entries as $entry) {
                $this->putRow($handle, [
                    $entry->user->name ?? '',
                    $entry->user->role ?? '',
                    optional($entry->work_date)->format('Y-m-d') ?: (string) $entry->work_date,
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
                ]);
            }
        });
    }

    public function summary(Request $request): StreamedResponse
    {
        $period = PayrollPeriodService::resolveFromRequest($request->all());

        $entries = TimeEntry::query()
            ->with('user')
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('user_id')
            ->orderBy('work_date')
            ->get();

        $summary = $entries
            ->groupBy('user_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $minutes = $rows->sum(fn (TimeEntry $entry) => $entry->worked_minutes);

                return [
                    'name' => $first->user->name ?? '',
                    'role' => $first->user->role ?? '',
                    'passes' => $rows->count(),
                    'minutes' => $minutes,
                    'formatted' => sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60),
                    'open' => $rows->where('status', TimeEntry::STATUS_OPEN)->count(),
                    'draft' => $rows->where('status', TimeEntry::STATUS_DRAFT)->count(),
                    'submitted' => $rows->where('status', TimeEntry::STATUS_SUBMITTED)->count(),
                    'corrected' => $rows->where('status', TimeEntry::STATUS_CORRECTED)->count(),
                    'approved' => $rows->where('status', TimeEntry::STATUS_APPROVED)->count(),
                ];
            })
            ->values();

        return $this->downloadCsv('tidrapport_summering_' . $period['file_label'] . '.csv', function ($handle) use ($summary) {
            $this->putRow($handle, [
                'Namn', 'Roll', 'Antal pass', 'Totala minuter', 'Total tid',
                'Öppna', 'Utkast', 'Inskickade', 'Korrigerade', 'Godkända',
            ]);

            foreach ($summary as $row) {
                $this->putRow($handle, [
                    $row['name'],
                    $row['role'],
                    $row['passes'],
                    $row['minutes'],
                    $row['formatted'],
                    $row['open'],
                    $row['draft'],
                    $row['submitted'],
                    $row['corrected'],
                    $row['approved'],
                ]);
            }
        });
    }

    private function downloadCsv(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            $callback($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function putRow($handle, array $row): void
    {
        fputcsv($handle, $row, ';');
    }
}
