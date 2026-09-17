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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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
        $guides = $this->builder->grid('Guider', $this->directory->forGuideSheet(), $this->from, $this->to);
        $kitchen = $this->builder->grid('Kök', $this->directory->forKitchenSheet(), $this->from, $this->to);

        return [
            new WorkShiftGridSheet('Guider', $guides['rows'], $guides['people']),
            new WorkShiftGridSheet('Kök', $kitchen['rows'], $kitchen['people']),
            new WorkShiftTemplateInstructionsSheet,
        ];
    }
}

class WorkShiftGridSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param  list<list<string>>  $rows
     * @param  list<array{name: string, time_col: int, role_col: int, options: list<string>}>  $people
     */
    public function __construct(
        private string $title,
        private array $rows,
        private array $people,
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

    /**
     * @return list<array{name: string, time_col: int, role_col: int, options: list<string>}>
     */
    public function people(): array
    {
        return $this->people;
    }

    public function styles(Worksheet $sheet): array
    {
        foreach (WorkShiftGridBuilder::HIDDEN_ROWS as $row) {
            $sheet->getRowDimension($row)->setVisible(false);
        }

        $lastRow = max(WorkShiftGridBuilder::FIRST_DAY_ROW, $sheet->getHighestRow());

        foreach ($this->people as $person) {
            $timeLetter = Coordinate::stringFromColumnIndex($person['time_col'] + 1);
            $roleLetter = Coordinate::stringFromColumnIndex($person['role_col'] + 1);

            $sheet->mergeCells("{$timeLetter}2:{$roleLetter}2");
            $sheet->getStyle("{$timeLetter}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$timeLetter}2")->getFont()->setBold(true);
            $sheet->getStyle("{$timeLetter}3:{$roleLetter}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $this->applyRoleList($sheet, $roleLetter, $lastRow, $person['options']);
        }

        $sheet->freezePane('B'.WorkShiftGridBuilder::FIRST_DAY_ROW);

        return [];
    }

    /**
     * @param  list<string>  $options
     */
    private function applyRoleList(Worksheet $sheet, string $column, int $lastRow, array $options): void
    {
        if ($options === []) {
            return;
        }

        $escaped = array_map(
            fn (string $option) => str_replace(['"', ','], ['', ' '], $option),
            $options,
        );
        $range = $column.WorkShiftGridBuilder::FIRST_DAY_ROW.':'.$column.$lastRow;
        $validation = $sheet->getCell($column.WorkShiftGridBuilder::FIRST_DAY_ROW)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Ogiltigt val');
        $validation->setError('Välj en roll i listan.');
        $validation->setFormula1('"'.implode(',', $escaped).'"');
        $sheet->setDataValidation($range, $validation);
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
            ['Varje person har två kolumner: Tid och Roll. Namnet ligger ovanför båda.'],
            ['Fliken Guider: admin, värd, guide och trainee. Fliken Kök: alla med restaurangrollen — även de som också är guide eller värd.'],
            ['På kök väljer du bland alla restaurangroller (Kök, Kassa, Disk, Buffé …). På guider väljer du bland personens roller.'],
            ['Tom tid och tom roll = jobbar inte. Bara tid räcker om personen har en roll. Annars måste rollen väljas.'],
            ['På kök räcker rollen (t.ex. Kassa) — standardtiden används. Eller fyll i tid och lämna rollen tom för standardrollen i köket.'],
            ['Raderna id, roll, funktion och tid är dolda — ta inte bort dem.'],
            ['Utbild, Sjuk och SLUTAR importeras inte.'],
            ['Lägg in ny personal under Användare och markera dem som aktiva innan du laddar ner en ny mall.'],
            ['TV-produktionens användare ingår inte.'],
            ['Funktioner i systemet: '.$functions],
        ];
    }
}
