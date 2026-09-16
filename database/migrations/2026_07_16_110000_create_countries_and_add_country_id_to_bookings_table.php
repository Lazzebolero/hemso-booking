<?php

use App\Support\CountryCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('name', 255);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_quick_pick')->default(false);
                $table->boolean('is_proposed')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });

            $now = now();

            foreach (CountryCatalog::defaults() as $country) {
                DB::table('countries')->insert([
                    'code' => $country['code'],
                    'name' => $country['name'],
                    'is_active' => true,
                    'is_quick_pick' => $country['is_quick_pick'],
                    'is_proposed' => false,
                    'sort_order' => $country['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'country_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'country_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('country_id');
            });
        }

        if (Schema::hasTable('countries')) {
            Schema::drop('countries');
        }
    }
};
