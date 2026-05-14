<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_categories')) {
            Schema::create('report_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 100)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_priorities')) {
            Schema::create('report_priorities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 100)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_statuses')) {
            Schema::create('report_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 100)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_locations')) {
            Schema::create('report_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 100)->nullable()->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_options')) {
            Schema::create('report_options', function (Blueprint $table) {
                $table->id();
                $table->string('type', 32);
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $this->seedReportLookupsIfEmpty();
    }

    public function down(): void
    {
        Schema::dropIfExists('report_options');
        Schema::dropIfExists('report_locations');
        Schema::dropIfExists('report_statuses');
        Schema::dropIfExists('report_priorities');
        Schema::dropIfExists('report_categories');
    }

    protected function seedReportLookupsIfEmpty(): void
    {
        $now = now();

        if (DB::table('report_categories')->count() === 0) {
            $rows = [
                ['name' => 'Byggnad', 'code' => 'building', 'sort_order' => 0],
                ['name' => 'El', 'code' => 'electricity', 'sort_order' => 1],
                ['name' => 'Säkerhet', 'code' => 'security', 'sort_order' => 2],
                ['name' => 'Städning', 'code' => 'cleaning', 'sort_order' => 3],
                ['name' => 'Utrustning', 'code' => 'equipment', 'sort_order' => 4],
                ['name' => 'Övrigt', 'code' => 'other', 'sort_order' => 5],
            ];
            foreach ($rows as $row) {
                DB::table('report_categories')->insert([
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('report_priorities')->count() === 0) {
            $rows = [
                ['name' => 'Låg', 'code' => 'low', 'sort_order' => 0],
                ['name' => 'Normal', 'code' => 'normal', 'sort_order' => 1],
                ['name' => 'Hög', 'code' => 'high', 'sort_order' => 2],
                ['name' => 'Akut', 'code' => 'urgent', 'sort_order' => 3],
            ];
            foreach ($rows as $row) {
                DB::table('report_priorities')->insert([
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('report_statuses')->count() === 0) {
            $rows = [
                ['name' => 'Ny', 'code' => 'new', 'sort_order' => 0],
                ['name' => 'Pågår', 'code' => 'in_progress', 'sort_order' => 1],
                ['name' => 'Väntar åtgärd', 'code' => 'waiting_action', 'sort_order' => 2],
                ['name' => 'Löst', 'code' => 'resolved', 'sort_order' => 3],
                ['name' => 'Stängd', 'code' => 'closed', 'sort_order' => 4],
            ];
            foreach ($rows as $row) {
                DB::table('report_statuses')->insert([
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('report_statuses')->where('code', 'open')->doesntExist()) {
            DB::table('report_statuses')->insert([
                'name' => 'Öppen',
                'code' => 'open',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
