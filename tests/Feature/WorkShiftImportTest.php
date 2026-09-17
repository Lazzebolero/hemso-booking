<?php

namespace Tests\Feature;

use App\Exports\WorkShiftTemplateExport;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use App\Services\WorkShiftStaffDirectory;
use App\Support\Roles;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class WorkShiftImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_template_lists_active_schedule_staff_and_excludes_others(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN, 'Adam Admin', true);
        $this->userWithRole(Roles::HOST, 'Vera Värd', true);
        $guide = $this->userWithRole(Roles::GUIDE, 'Greta Guide', true);
        $this->userWithRole(Roles::ELEV, 'Tina Trainee', true);
        $cook = $this->userWithRole(Roles::RESTAURANT, 'Kalle Kock', true);
        $this->userWithRoles([Roles::GUIDE, Roles::RESTAURANT], 'Bella Båda', true);
        $this->userWithRole(Roles::GUIDE, 'Inaktiv Iris', false);
        $this->userWithRole(Roles::PRODUKTION_PERSONAL, 'TV Tomas', true);

        $staff = app(WorkShiftStaffDirectory::class)->forTemplate();
        $names = $staff->pluck('name')->all();

        $this->assertSame([
            'Adam Admin',
            'Bella Båda',
            'Greta Guide',
            'Tina Trainee',
            'Vera Värd',
            'Kalle Kock',
        ], $names);
        $this->assertNotContains('Inaktiv Iris', $names);
        $this->assertNotContains('TV Tomas', $names);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.work-shifts.index'))
            ->assertOk()
            ->assertSee('Ladda ner mall', false)
            ->assertSee('Importera schema', false)
            ->assertSee('Tina Trainee', false)
            ->assertDontSee('Inaktiv Iris', false)
            ->assertDontSee('TV Tomas', false);

        Excel::fake();

        $from = now()->startOfMonth()->toDateString();
        $to = now()->addMonths(2)->endOfMonth()->toDateString();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.work-shifts.template'))
            ->assertOk();

        Excel::assertDownloaded('arbetsschema-mall-'.$from.'-'.$to.'.xlsx', function (WorkShiftTemplateExport $export) use ($guide, $from) {
            $sheets = $export->sheets();
            $guideNames = $sheets[0]->array()[1];
            $kitchenNames = $sheets[1]->array()[1];
            $guideIds = $sheets[0]->array()[2];
            $guideNameIndex = array_search('Greta Guide', $guideNames, true);

            return $sheets[0]->title() === 'Guider'
                && $sheets[1]->title() === 'Kök'
                && in_array('Greta Guide', $guideNames, true)
                && in_array('Bella Båda', $guideNames, true)
                && in_array('Kalle Kock', $kitchenNames, true)
                && ! in_array('Kalle Kock', $guideNames, true)
                && ! in_array('TV Tomas', $guideNames, true)
                && $guideNameIndex !== false
                && (int) $guideIds[$guideNameIndex] === $guide->id
                && $sheets[0]->array()[0][1] === $from;
        });
    }

    public function test_admin_can_preview_and_import_work_shifts_from_csv(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN, 'Adam Admin', true);
        $guide = $this->userWithRole(Roles::GUIDE, 'Greta Guide', true);
        $cook = $this->userWithRole(Roles::RESTAURANT, 'Kalle Kock', true);

        $csv = implode("\n", [
            'Datum,E-post,Namn,Roll,Funktion,Starttid,Sluttid,Status,Anteckning',
            '2026-06-15,'.$guide->email.',Greta,Guide,,09:00,17:00,Planerat,Morgon',
            '2026-06-15,'.$cook->email.',Kalle,Restaurang,kassa,08:00,16:00,,',
            '2026-06-15,saknas@example.com,Okänd,Guide,,09:00,17:00,,',
        ]);

        $file = UploadedFile::fake()->createWithContent('schema.csv', $csv);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.work-shifts.import.store'), ['file' => $file])
            ->assertOk()
            ->assertSee('Förhandsgranska import', false)
            ->assertSee('Greta Guide', false)
            ->assertSee('Kalle Kock', false)
            ->assertSee('saknas@example.com', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.work-shifts.import.confirm'))
            ->assertRedirect(route('admin.work-shifts.index'));

        $this->assertDatabaseHas('work_shifts', [
            'user_id' => $guide->id,
            'shift_role' => Roles::GUIDE,
            'start_time' => '09:00',
        ]);
        $this->assertDatabaseHas('work_shifts', [
            'user_id' => $cook->id,
            'shift_role' => Roles::RESTAURANT,
            'shift_function' => 'kassa',
        ]);
    }

    public function test_admin_can_import_grid_template_using_user_ids(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN, 'Adam Admin', true);
        $guide = $this->userWithRole(Roles::GUIDE, 'Greta Guide', true);
        $cook = $this->userWithRole(Roles::RESTAURANT, 'Kalle Kock', true);

        $spreadsheet = new Spreadsheet;
        $guideSheet = $spreadsheet->getActiveSheet();
        $guideSheet->setTitle('Guider');
        $guideSheet->fromArray([
            ['Guider', '2026-06-15', '2026-06-16'],
            ['', $guide->name],
            ['id', $guide->id],
            ['roll', 'Guide'],
            ['funktion', ''],
            ['tid', '10:00'],
            ['2026-06-15 mån', '11:00'],
            ['2026-06-16 tis', 'Utbild'],
        ]);

        $kitchenSheet = $spreadsheet->createSheet();
        $kitchenSheet->setTitle('Kök');
        $kitchenSheet->fromArray([
            ['Kök', '2026-06-15', '2026-06-16'],
            ['', $cook->name],
            ['id', $cook->id],
            ['roll', 'Restaurang'],
            ['funktion', 'Kock'],
            ['tid', '10:00-16:00'],
            ['2026-06-15 mån', 'Kassa'],
            ['2026-06-16 tis', '10:00-18:00'],
        ]);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'schema-grid-'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile($path, 'schema.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.work-shifts.import.store'), ['file' => $file])
            ->assertOk()
            ->assertSee('Greta Guide', false)
            ->assertSee('Kalle Kock', false)
            ->assertDontSee('Utbild', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.work-shifts.import.confirm'))
            ->assertRedirect(route('admin.work-shifts.index'));

        $this->assertTrue(
            WorkShift::query()
                ->where('user_id', $guide->id)
                ->whereDate('shift_date', '2026-06-15')
                ->where('shift_role', Roles::GUIDE)
                ->where(function ($query) {
                    $query->where('start_time', '11:00')->orWhere('start_time', '11:00:00');
                })
                ->exists()
        );
        $this->assertTrue(
            WorkShift::query()
                ->where('user_id', $cook->id)
                ->whereDate('shift_date', '2026-06-15')
                ->where('shift_role', Roles::RESTAURANT)
                ->where('shift_function', 'kassa')
                ->exists()
        );
        $this->assertTrue(
            WorkShift::query()
                ->where('user_id', $cook->id)
                ->whereDate('shift_date', '2026-06-16')
                ->where('shift_function', 'kock')
                ->exists()
        );
        $this->assertSame(0, WorkShift::query()->where('user_id', $guide->id)->whereDate('shift_date', '2026-06-16')->count());

        @unlink($path);
    }

    public function test_import_skips_duplicate_shifts(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN, 'Adam Admin', true);
        $guide = $this->userWithRole(Roles::GUIDE, 'Greta Guide', true);

        WorkShift::query()->create([
            'user_id' => $guide->id,
            'shift_date' => '2026-06-15',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'shift_role' => Roles::GUIDE,
            'status' => 'planned',
        ]);

        $csv = implode("\n", [
            'Datum,E-post,Namn,Roll,Funktion,Starttid,Sluttid,Status,Anteckning',
            '2026-06-15,'.$guide->email.',Greta,Guide,,09:00,17:00,Planerat,',
        ]);

        $file = UploadedFile::fake()->createWithContent('schema.csv', $csv);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.work-shifts.import.store'), ['file' => $file])
            ->assertOk()
            ->assertSee('Inga nya arbetspass att skapa.', false);

        $this->assertSame(1, WorkShift::query()->where('user_id', $guide->id)->count());
    }

    public function test_host_cannot_import_work_shifts(): void
    {
        $host = $this->userWithRole(Roles::HOST, 'Vera Värd', true);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('admin.work-shifts.import'))
            ->assertForbidden();
    }

    private function userWithRole(string $roleSlug, string $name, bool $active): User
    {
        return $this->userWithRoles([$roleSlug], $name, $active);
    }

    /**
     * @param  list<string>  $roleSlugs
     */
    private function userWithRoles(array $roleSlugs, string $name, bool $active): User
    {
        $roles = Role::query()->whereIn('slug', $roleSlugs)->get();

        $this->assertCount(count($roleSlugs), $roles);

        $user = User::factory()->create([
            'name' => $name,
            'is_active' => $active,
        ]);
        $user->assignRoles($roles->all());

        return $user;
    }
}
