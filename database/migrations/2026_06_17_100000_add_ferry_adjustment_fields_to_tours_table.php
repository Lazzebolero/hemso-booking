<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->time('original_start_time')->nullable()->after('start_time');
            $table->time('original_end_time')->nullable()->after('end_time');
            $table->timestamp('ferry_adjusted_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'original_start_time',
                'original_end_time',
                'ferry_adjusted_at',
            ]);
        });
    }
};
