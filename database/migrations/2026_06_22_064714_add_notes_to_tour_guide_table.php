<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_guide', function (Blueprint $table) {
            $table->string('notes', 255)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('tour_guide', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
