<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('exclude_from_schedule_statistics')
                ->default(false)
                ->after('exclude_from_booking_sequence');
        });

        DB::table('tours')
            ->where('id', 368)
            ->update(['exclude_from_schedule_statistics' => true]);
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('exclude_from_schedule_statistics');
        });
    }
};
