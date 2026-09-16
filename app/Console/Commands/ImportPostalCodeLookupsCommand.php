<?php

namespace App\Console\Commands;

use App\Models\PostalCodeLookup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportPostalCodeLookupsCommand extends Command
{
    protected $signature = 'postal-codes:import-lookups
                            {path? : Sökväg till CSV (standard: database/data/postal_code_lookups.csv)}
                            {--truncate : Töm tabellen före import}';

    protected $description = 'Importera postnummer → ort/kommun/län från CSV';

    public function handle(): int
    {
        $path = $this->argument('path')
            ?: database_path('data/postal_code_lookups.csv');

        if (! File::exists($path)) {
            $fallback = database_path('data/postal_code_lookups_sample.csv');
            if (File::exists($fallback)) {
                $this->warn("Hittade inte {$path}, använder sample-filen i stället.");
                $path = $fallback;
            } else {
                $this->error("Filen saknas: {$path}");

                return self::FAILURE;
            }
        }

        if ($this->option('truncate')) {
            PostalCodeLookup::query()->delete();
            $this->warn('Tömde postal_code_lookups.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error('Kunde inte öppna filen.');

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('Tom CSV.');

            return self::FAILURE;
        }

        $header = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $required = ['postal_code', 'locality'];

        foreach ($required as $column) {
            if (! in_array($column, $header, true)) {
                fclose($handle);
                $this->error("CSV saknar kolumnen {$column}.");

                return self::FAILURE;
            }
        }

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $data = [];
            foreach ($header as $index => $column) {
                $data[$column] = trim((string) ($row[$index] ?? ''));
            }

            $code = PostalCodeLookup::normalize((string) ($data['postal_code'] ?? ''));
            $locality = trim((string) ($data['locality'] ?? ''));

            if ($code === null || $locality === '') {
                $skipped++;

                continue;
            }

            PostalCodeLookup::query()->updateOrCreate(
                ['postal_code' => $code],
                [
                    'locality' => $locality,
                    'municipality_code' => ($data['municipality_code'] ?? '') !== '' ? $data['municipality_code'] : null,
                    'municipality_name' => ($data['municipality_name'] ?? '') !== '' ? $data['municipality_name'] : null,
                    'county_code' => ($data['county_code'] ?? '') !== '' ? $data['county_code'] : null,
                    'county_name' => ($data['county_name'] ?? '') !== '' ? $data['county_name'] : null,
                ]
            );

            $imported++;
        }

        fclose($handle);

        $this->info("Importerade {$imported} postnummer ({$skipped} hoppades över).");

        return self::SUCCESS;
    }
}
