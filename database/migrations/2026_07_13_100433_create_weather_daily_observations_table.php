<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('weather_daily_observations')) {
            Schema::table('weather_daily_observations', function (Blueprint $table) {
                $table->index(['iso_week', 'iso_weekday', 'observation_date'], 'weather_daily_iso_slot_date_idx');
            });

            return;
        }

        Schema::create('weather_daily_observations', function (Blueprint $table) {
            $table->id();
            $table->date('observation_date')->unique();
            $table->string('station_name', 64);
            $table->unsignedInteger('station_id');
            $table->decimal('temp_min', 5, 1)->nullable();
            $table->decimal('temp_max', 5, 1)->nullable();
            $table->decimal('precipitation_mm', 6, 1)->nullable();
            $table->decimal('wind_speed_max', 5, 1)->nullable();
            $table->decimal('wind_gust_max', 5, 1)->nullable();
            $table->unsignedTinyInteger('iso_week');
            $table->unsignedSmallInteger('iso_week_year');
            $table->unsignedTinyInteger('iso_weekday');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['iso_week', 'iso_weekday', 'observation_date'], 'weather_daily_iso_slot_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_daily_observations');
    }
};
