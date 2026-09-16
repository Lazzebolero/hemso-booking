<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_types', function (Blueprint $table) {
            $table->boolean('include_in_booking_sequence')->default(false)->after('is_default');
        });

        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('exclude_from_booking_sequence')->default(false)->after('default_includes_meal');
        });

        if (Schema::hasTable('tour_types')) {
            DB::table('tour_types')
                ->whereRaw('LOWER(name) = ?', ['guidad visning'])
                ->update(['include_in_booking_sequence' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('exclude_from_booking_sequence');
        });

        Schema::table('tour_types', function (Blueprint $table) {
            $table->dropColumn('include_in_booking_sequence');
        });
    }
};
