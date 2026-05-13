<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_options', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('report_options')->insert([
            ['type' => 'category', 'name' => 'Städ', 'sort_order' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'name' => 'Teknik', 'sort_order' => 2, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'name' => 'Säkerhet', 'sort_order' => 3, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'name' => 'Skyltning', 'sort_order' => 4, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'name' => 'Toalett', 'sort_order' => 5, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'name' => 'Övrigt', 'sort_order' => 6, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'priority', 'name' => 'Låg', 'sort_order' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'priority', 'name' => 'Medel', 'sort_order' => 2, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'priority', 'name' => 'Hög', 'sort_order' => 3, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'priority', 'name' => 'Akut', 'sort_order' => 4, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('report_options');
    }
};
