<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_types', function (Blueprint $table) {
            $table->boolean('auto_complete_enabled')->default(true)->after('default_duration_minutes');
            $table->unsignedSmallInteger('auto_complete_grace_minutes')->default(15)->after('auto_complete_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('tour_types', function (Blueprint $table) {
            $table->dropColumn(['auto_complete_enabled', 'auto_complete_grace_minutes']);
        });
    }
};
