<?php

namespace App\Exports;

use App\Models\RestaurantFunction;
use App\Services\WorkShiftGridBuilder;
use App\Services\WorkShiftStaffDirectory;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkShiftTemplateExport implements Export, WithMultipleSheets
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
        private WorkShiftStaffDirectory $directory,
        private WorkShiftGridBuilder $builder,
    ) {}

    public function sheets(): array
    {
        return [
            new WorkShiftGridSheet(
                'Guider',
                $this->builder->rows('Guider', $this->directory->forGuideSheet(), $this->from, $this->to),
            ),
            new WorkShiftGridSheet(
                'Kök',
                $this->builder->rows('Kök', $this->directory->forKitchenSheet(), $this->from, $this->to),
            ),
            new WorkShiftTemplateInstructionsSheet,
        ];
    }
}

class WorkShiftGridSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param  list<list<string>>  $rows
     */
    public function __construct(
        private string $title,
        private array $rows,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        foreach ([3, 4, 5, 6] as $row) {
            $sheet->getRowDimension($row)->setVisible(false);
        }

        return [];
    }
}

class WorkShiftTemplateInstructionsSheet implements FromArray, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return 'Instruktion';
    }

    public function array(): array
    {
        $functions = collect(RestaurantFunction::activeOptions())
            ->map(fn (string $name, string $slug) => $name.' ('.$slug.')')
            ->implode(', ');

        return [
            ['Välj period när du laddar ner mallen. Datumraderna är redan ifyllda.'],
            ['Fliken Guider: admin, värd, guide och trainee. Fliken Kök: restaurangpersonal.'],
            ['Namn syns i kolumnen. Raderna id, roll, funktion och tid är dolda — ta inte bort dem.'],
            ['Tom cell = jobbar inte. Skriv tid (10:00 eller 10:00-16:00), funktion (Kök, Kassa, Disk, Buffé, Glassbar) eller båda (10:00 Kassa).'],
            ['Utbild, Sjuk och SLUTAR importeras inte.'],
            ['Lägg in ny personal under Användare och markera dem som aktiva innan du laddar ner en ny mall.'],
            ['TV-produktionens användare ingår inte.'],
            ['Funktioner i systemet: '.$functions],
        ];
    }
}
