<?php

namespace App\Imports;

use App\Models\HistoricalDailyVisitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class HistoricalDailyVisitorsImport implements Import, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> */
    public array $detectedHeadingKeys = [];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->errors[] = 'Filen innehåller inga datarader efter rubrikraden.';

            return;
        }

        $this->detectedHeadingKeys = array_keys(collect($rows->first())->toArray());

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $values = $this->normalizeRow(collect($row)->toArray());

                if ($this->rowIsEffectivelyEmpty($values)) {
                    continue;
                }

                $dateValue = $this->resolveDateValue($values);
                $countValue = $this->resolveCountValue($values);

                if ($dateValue === null || $dateValue === '') {
                    $this->skipped++;

                    continue;
                }

                if ($countValue === null || $countValue === '') {
                    $this->errors[] = "Rad {$rowNumber}: Deltagare saknas.";
                    $this->skipped++;

                    continue;
                }

                $date = $this->parseDate($dateValue);
                $count = $this->parseCount($countValue);

                if ($count < 0) {
                    $this->errors[] = "Rad {$rowNumber}: Deltagare kan inte vara negativt.";
                    $this->skipped++;

                    continue;
                }

                $this->saveDay($date, $count);

                $this->imported++;
            } catch (\Throwable $exception) {
                $this->errors[] = "Rad {$rowNumber}: {$exception->getMessage()}";
                $this->skipped++;
            }
        }

        if ($this->imported === 0 && $this->skipped > 0) {
            $this->errors[] = $this->buildNoImportSummaryMessage();
        }
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<int|string, mixed>
     */
    private function normalizeRow(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $normalized[$key] = $value;

                continue;
            }

            $normalized[Str::slug(trim((string) $key), '_')] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    private function rowIsEffectivelyEmpty(array $values): bool
    {
        return collect($values)->every(function (mixed $value): bool {
            if ($value === null) {
                return true;
            }

            if (is_string($value)) {
                return trim($value) === '';
            }

            return false;
        });
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    private function resolveDateValue(array $values): mixed
    {
        foreach (['datum', 'date', 'stat_date', 'dag', 'besoksdatum', 'turdatum'] as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return $this->valueByPosition($values, 0);
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    private function resolveCountValue(array $values): mixed
    {
        foreach ([
            'deltagare',
            'deltagare_totalt',
            'participants',
            'participant_count',
            'antal',
            'antal_deltagare',
            'besokare',
            'count',
            'total',
            'summa',
        ] as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return $this->valueByPosition($values, 1);
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    private function valueByPosition(array $values, int $position): mixed
    {
        if (array_key_exists($position, $values) && $values[$position] !== null && $values[$position] !== '') {
            return $values[$position];
        }

        return collect($values)->values()->get($position);
    }

    private function parseCount(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $string = trim((string) $value);
        $string = str_replace(["\xc2\xa0", ' '], '', $string);
        $string = str_replace(',', '.', $string);

        return (int) round((float) $string);
    }

    private function buildNoImportSummaryMessage(): string
    {
        $headings = $this->detectedHeadingKeys === []
            ? '(inga kolumner upptäcktes)'
            : implode(', ', array_map('strval', $this->detectedHeadingKeys));

        return "Inga rader kunde importeras. Upptäckta kolumnrubriker: {$headings}. "
            .'Använd rubrikerna Datum och Deltagare på första raden, eller två kolumner utan rubrik (datum i kolumn A, antal i kolumn B). '
            .'Kontrollera att datum inte ligger i en annan flik eller att första raden inte är en titelrad.';
    }

    private function saveDay(Carbon $date, int $count): void
    {
        $attributes = [
            'participant_count' => $count,
            ...HistoricalDailyVisitor::isoAttributesFromDate($date),
            'imported_at' => now(),
        ];

        $record = HistoricalDailyVisitor::query()
            ->whereDate('stat_date', $date)
            ->first();

        if ($record) {
            $record->update($attributes);

            return;
        }

        HistoricalDailyVisitor::query()->create([
            'stat_date' => $date->toDateString(),
            ...$attributes,
        ]);
    }

    private function parseDate(mixed $value): Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
        }

        $string = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $string)) {
            return Carbon::parse($string)->startOfDay();
        }

        if (preg_match('/^(\d{1,2})[-.\/](\d{1,2})[-.\/](\d{4})$/', $string, $matches)) {
            return Carbon::create((int) $matches[3], (int) $matches[2], (int) $matches[1])->startOfDay();
        }

        if (preg_match('/^(\d{1,2})[-.\/](\d{1,2})[-.\/](\d{2})$/', $string, $matches)) {
            return Carbon::create(2000 + (int) $matches[3], (int) $matches[2], (int) $matches[1])->startOfDay();
        }

        return Carbon::parse($string)->startOfDay();
    }
}
