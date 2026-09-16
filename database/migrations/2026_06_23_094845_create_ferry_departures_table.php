<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ferry_departures', function (Blueprint $table) {
            $table->id();
            $table->string('direction');
            $table->string('day_type');
            $table->time('departure_time');
            $table->boolean('requires_call')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['direction', 'day_type', 'departure_time']);
            $table->index(['direction', 'day_type', 'departure_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ferry_departures');
    }
};
