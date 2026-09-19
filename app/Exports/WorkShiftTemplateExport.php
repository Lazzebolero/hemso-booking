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
        $lists = $this->builder->choiceLists($guides['people'], $kitchen['people']);

        return [
            new WorkShiftGridSheet('Guider', $guides['rows'], $lists['guides']),
            new WorkShiftGridSheet('Kök', $kitchen['rows'], $lists['kitchen']),
            new WorkShiftChoiceListsSheet($lists['rows']),
            new WorkShiftTemplateInstructionsSheet,
        ];
    }
}

class WorkShiftGridSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param  list<list<string>>  $rows
     * @param  list<array{name: string, time_col: int, role_col: int, options: list<string>, list_range?: ?string}>  $people
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

            $this->applyRoleList($sheet, $roleLetter, $lastRow, $person['list_range'] ?? null);
        }

        $sheet->freezePane('B'.WorkShiftGridBuilder::FIRST_DAY_ROW);

        return [];
    }

    private function applyRoleList(Worksheet $sheet, string $column, int $lastRow, ?string $listRange): void
    {
        if ($listRange === null || $listRange === '') {
            return;
        }

        $range = $column.WorkShiftGridBuilder::FIRST_DAY_ROW.':'.$column.$lastRow;
        $validation = $sheet->getCell($column.WorkShiftGridBuilder::FIRST_DAY_ROW)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Ogiltigt val');
        $validation->setError('Välj en roll i listan.');
        $validation->setFormula1($listRange);
        $sheet->setDataValidation($range, $validation);
    }
}

class WorkShiftChoiceListsSheet implements FromArray, WithStyles, WithTitle
{
    /**
     * @param  list<list<string>>  $rows
     */
    public function __construct(
        private array $rows,
    ) {}

    public function title(): string
    {
        return WorkShiftGridBuilder::LIST_SHEET_TITLE;
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
        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

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
            ['Välj period när du laddar ner mallen. Datumraderna är ifyllda, och redan sparade pass ligger i cellerna.'],
            ['Varje person har två kolumner: Tid och Roll. Namnet ligger ovanför båda.'],
            ['Fliken Guider: admin, värd, guide och trainee. Fliken Kök: alla med restaurangrollen — även de som också är guide eller värd.'],
            ['På kök och guider väljer du roll i listan. Kök har personens roller plus alla restaurangstationer (Kök, Kassa, Disk, Buffé …).'],
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
