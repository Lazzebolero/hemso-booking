<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WorkShiftSpreadsheet implements Import, ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void {}
}
