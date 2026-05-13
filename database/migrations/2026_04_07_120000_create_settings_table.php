<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'default_tour_capacity', 'value' => '25', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'timezone', 'value' => 'Europe/Stockholm', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_generate_tour_title', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_generate_booking_name', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
