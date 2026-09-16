<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_daily_visitors', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date')->unique();
            $table->unsignedInteger('participant_count');
            $table->unsignedTinyInteger('iso_week');
            $table->smallInteger('iso_week_year');
            $table->unsignedTinyInteger('iso_weekday');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['iso_week_year', 'iso_week', 'iso_weekday'], 'hdv_iso_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_daily_visitors');
    }
};
