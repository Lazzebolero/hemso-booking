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
            return;
        }

        if (! Schema::hasColumn('countries', 'is_proposed')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->boolean('is_proposed')->default(false)->after('is_quick_pick');
            });
        }

        if (Schema::hasColumn('countries', 'flag')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('flag');
            });
        }

        $now = now();

        foreach (CountryCatalog::defaults() as $country) {
            $existing = DB::table('countries')->where('code', $country['code'])->first();

            if ($existing === null) {
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

                continue;
            }

            DB::table('countries')
                ->where('id', $existing->id)
                ->update([
                    'name' => $country['name'],
                    'sort_order' => $country['sort_order'],
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('countries')) {
            return;
        }

        if (Schema::hasColumn('countries', 'is_proposed')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('is_proposed');
            });
        }
    }
};
