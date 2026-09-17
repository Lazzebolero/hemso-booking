<?php

namespace App\Exports;

use App\Models\RestaurantFunction;
use App\Models\User;
use App\Services\WorkShiftStaffDirectory;
use App\Support\Roles;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class WorkShiftTemplateExport implements Export, WithMultipleSheets
{
    public function __construct(
        private WorkShiftStaffDirectory $directory,
    ) {}

    public function sheets(): array
    {
        $staff = $this->directory->forTemplate();

        return [
            new WorkShiftTemplateShiftsSheet,
            new WorkShiftTemplateStaffSheet($staff, $this->directory),
            new WorkShiftTemplateInstructionsSheet,
        ];
    }
}

class WorkShiftTemplateShiftsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Arbetspass';
    }

    public function headings(): array
    {
        return [
            'Datum',
            'E-post',
            'Namn',
            'Roll',
            'Funktion',
            'Starttid',
            'Sluttid',
            'Status',
            'Anteckning',
        ];
    }

    public function collection(): Collection
    {
        return collect();
    }
}

class WorkShiftTemplateStaffSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  Collection<int, User>  $staff
     */
    public function __construct(
        private Collection $staff,
        private WorkShiftStaffDirectory $directory,
    ) {}

    public function title(): string
    {
        return 'Personal';
    }

    public function headings(): array
    {
        return [
            'Namn',
            'E-post',
            'Roller',
            'Grupp',
        ];
    }

    public function collection(): Collection
    {
        return $this->staff->map(function (User $user) {
            $roleLabels = $user->roles
                ->whereIn('slug', Roles::scheduleStaffRoles())
                ->sortBy('name')
                ->pluck('name')
                ->values()
                ->implode(', ');

            return [
                $user->name,
                $user->email,
                $roleLabels,
                $this->directory->templateGroupLabel($user),
            ];
        });
    }
}

class WorkShiftTemplateInstructionsSheet implements FromCollection, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return 'Instruktion';
    }

    public function collection(): Collection
    {
        $functions = collect(RestaurantFunction::activeOptions())
            ->map(fn (string $name, string $slug) => $name.' ('.$slug.')')
            ->implode(', ');

        return collect([
            ['Planera i fliken Arbetspass. Kopiera e-post från fliken Personal.'],
            ['Lägg in personal under Användare och markera dem som aktiva innan du laddar ner en ny mall.'],
            ['TV-produktionens användare ingår inte.'],
            [''],
            ['Kolumner i Arbetspass: Datum, E-post, Namn, Roll, Funktion, Starttid, Sluttid, Status, Anteckning.'],
            ['E-post måste matcha en aktiv person i systemet. Namn är bara till för dig som planerar.'],
            ['Roll: Admin, Värd, Guide, Trainee / elev eller Restaurang.'],
            ['Funktion krävs bara för restaurang: '.$functions],
            ['Tider som 09:00. Status tom = Planerat. Andra: Bekräftat, Ändrat, Inställt.'],
            ['Samma person, datum, roll och starttid som redan finns hoppas över vid import.'],
        ]);
    }
}
