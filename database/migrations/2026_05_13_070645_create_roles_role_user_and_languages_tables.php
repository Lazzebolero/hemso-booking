<?php

use App\Support\Roles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code');
        });

        $now = now();

        DB::table('roles')->insert([
            [
                'name' => 'Admin',
                'slug' => Roles::ADMIN,
                'description' => 'Administration och full kontroll',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Värd',
                'slug' => Roles::HOST,
                'description' => 'Bokningar och turplanering',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Guide',
                'slug' => Roles::GUIDE,
                'description' => 'Mobilanpassat guideläge',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Restaurang',
                'slug' => Roles::RESTAURANT,
                'description' => 'Statistik och meddelanden',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Restaurang statistik',
                'slug' => Roles::RESTAURANT_STATISTIK,
                'description' => 'Ren statistiksida för restaurangskärm utan navigation',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('languages')->insert([
            'name' => 'Svenska',
            'code' => 'sv',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('languages');
    }
};
