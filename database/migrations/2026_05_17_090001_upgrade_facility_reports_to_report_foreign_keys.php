<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facility_reports')) {
            return;
        }

        if (Schema::hasColumn('facility_reports', 'category_id')) {
            return;
        }

        if (! Schema::hasColumn('facility_reports', 'category')) {
            return;
        }

        Schema::table('facility_reports', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description');
            $table->foreignId('priority_id')->nullable()->after('category_id');
            $table->foreignId('status_id')->nullable()->after('priority_id');
            $table->foreignId('location_id')->nullable()->after('status_id');
            $table->string('location_text')->nullable()->after('location_id');
        });

        $reports = DB::table('facility_reports')->select('*')->get();

        foreach ($reports as $row) {
            $categoryId = DB::table('report_categories')->where('code', (string) $row->category)->value('id');
            $priorityId = DB::table('report_priorities')->where('code', (string) $row->priority)->value('id');
            $statusId = DB::table('report_statuses')->where('code', (string) $row->status)->value('id');

            DB::table('facility_reports')->where('id', $row->id)->update([
                'category_id' => $categoryId,
                'priority_id' => $priorityId,
                'status_id' => $statusId,
                'location_id' => null,
                'location_text' => $row->location,
            ]);
        }

        Schema::table('facility_reports', function (Blueprint $table) {
            $table->dropColumn(['category', 'priority', 'status', 'location']);
        });
    }

    public function down(): void
    {
        //
    }
};
