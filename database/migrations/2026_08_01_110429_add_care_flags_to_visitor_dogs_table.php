<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_dogs', function (Blueprint $table) {
            $table->json('care_flags')->nullable()->after('tour_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_dogs', function (Blueprint $table) {
            $table->dropColumn('care_flags');
        });
    }
};
