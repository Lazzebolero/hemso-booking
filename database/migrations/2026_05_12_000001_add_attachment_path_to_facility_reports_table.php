<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facility_reports') || Schema::hasColumn('facility_reports', 'attachment_path')) {
            return;
        }

        Schema::table('facility_reports', function (Blueprint $table) {
            $table->string('attachment_path', 512)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('facility_reports') || ! Schema::hasColumn('facility_reports', 'attachment_path')) {
            return;
        }

        Schema::table('facility_reports', function (Blueprint $table) {
            $table->dropColumn('attachment_path');
        });
    }
};
