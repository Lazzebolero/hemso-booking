<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (! Schema::hasColumn('bookings', 'includes_meal')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->boolean('includes_meal')->default(false)->after('is_walk_in');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (Schema::hasColumn('bookings', 'includes_meal')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('includes_meal');
            });
        }
    }
};
