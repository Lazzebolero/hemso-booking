<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_functions', function (Blueprint $table) {
            $table->time('default_start_time')->nullable()->after('is_active');
            $table->time('default_end_time')->nullable()->after('default_start_time');
        });

        DB::table('restaurant_functions')->update([
            'default_start_time' => '10:00:00',
            'default_end_time' => '16:00:00',
        ]);
    }

    public function down(): void
    {
        Schema::table('restaurant_functions', function (Blueprint $table) {
            $table->dropColumn(['default_start_time', 'default_end_time']);
        });
    }
};
