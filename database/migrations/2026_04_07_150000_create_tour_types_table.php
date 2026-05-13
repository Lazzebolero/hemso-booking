<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        DB::table('tour_types')->insert([
            ['name' => 'Guidad visning', 'sort_order' => 1, 'is_active' => 1, 'is_default' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Skolvisning', 'sort_order' => 2, 'is_active' => 1, 'is_default' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gruppvisning', 'sort_order' => 3, 'is_active' => 1, 'is_default' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Specialvisning', 'sort_order' => 4, 'is_active' => 1, 'is_default' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_types');
    }
};
