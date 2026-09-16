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
        $now = now();

        foreach ([
            [
                'name' => 'Produktion admin',
                'slug' => Roles::PRODUKTION_ADMIN,
                'description' => 'TV-produktion: in/ut i berget och hantering av personer',
            ],
            [
                'name' => 'Produktion personal',
                'slug' => Roles::PRODUKTION_PERSONAL,
                'description' => 'TV-produktion: in/ut i berget och gruppstämpel för deltagare',
            ],
        ] as $role) {
            if (DB::table('roles')->where('slug', $role['slug'])->exists()) {
                continue;
            }

            DB::table('roles')->insert([
                'name' => $role['name'],
                'slug' => $role['slug'],
                'description' => $role['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('productions')) {
            Schema::create('productions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('starts_on');
                $table->date('ends_on');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('production_people')) {
            Schema::create('production_people', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('kind', 32);
                $table->boolean('is_inside')->default(false);
                $table->timestamp('departed_at')->nullable();
                $table->date('departed_on')->nullable();
                $table->timestamp('last_presence_at')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['production_id', 'kind']);
                $table->index(['production_id', 'is_inside']);
                $table->unique(['production_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('production_presence_logs')) {
            Schema::create('production_presence_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
                $table->foreignId('production_person_id')->constrained('production_people')->cascadeOnDelete();
                $table->string('direction', 8);
                $table->timestamp('occurred_at');
                $table->boolean('with_group')->default(false);
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['production_id', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_presence_logs');
        Schema::dropIfExists('production_people');
        Schema::dropIfExists('productions');

        DB::table('roles')->whereIn('slug', [
            Roles::PRODUKTION_ADMIN,
            Roles::PRODUKTION_PERSONAL,
        ])->delete();
    }
};
