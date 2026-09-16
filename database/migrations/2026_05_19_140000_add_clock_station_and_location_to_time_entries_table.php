<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->string('clock_in_station', 32)->nullable()->after('clock_out_at_original');
            $table->string('clock_out_station', 32)->nullable()->after('clock_in_station');

            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_out_station');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->unsignedInteger('clock_in_location_accuracy_m')->nullable()->after('clock_in_longitude');
            $table->string('clock_in_location_status', 20)->nullable()->after('clock_in_location_accuracy_m');

            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_in_location_status');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            $table->unsignedInteger('clock_out_location_accuracy_m')->nullable()->after('clock_out_longitude');
            $table->string('clock_out_location_status', 20)->nullable()->after('clock_out_location_accuracy_m');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_station',
                'clock_out_station',
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_location_accuracy_m',
                'clock_in_location_status',
                'clock_out_latitude',
                'clock_out_longitude',
                'clock_out_location_accuracy_m',
                'clock_out_location_status',
            ]);
        });
    }
};
