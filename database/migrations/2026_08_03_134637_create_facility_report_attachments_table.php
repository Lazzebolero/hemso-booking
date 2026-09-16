<?php

use App\Models\FacilityReport;
use App\Models\FacilityReportAttachment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_report_id')->constrained('facility_reports')->cascadeOnDelete();
            $table->string('path', 512);
            $table->string('original_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        FacilityReport::query()
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->orderBy('id')
            ->each(function (FacilityReport $report): void {
                FacilityReportAttachment::query()->create([
                    'facility_report_id' => $report->id,
                    'path' => $report->attachment_path,
                    'original_name' => null,
                    'sort_order' => 0,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_report_attachments');
    }
};
