<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_role_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('role_slug', 50)->unique();
            $table->time('default_start_time');
            $table->time('default_end_time')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('shift_role_defaults')->insert([
            ['role_slug' => 'admin', 'default_start_time' => '10:00:00', 'default_end_time' => null, 'created_at' => $now, 'updated_at' => $now],
            ['role_slug' => 'host', 'default_start_time' => '10:00:00', 'default_end_time' => null, 'created_at' => $now, 'updated_at' => $now],
            ['role_slug' => 'guide', 'default_start_time' => '10:00:00', 'default_end_time' => null, 'created_at' => $now, 'updated_at' => $now],
            ['role_slug' => 'elev', 'default_start_time' => '10:00:00', 'default_end_time' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_role_defaults');
    }
};
