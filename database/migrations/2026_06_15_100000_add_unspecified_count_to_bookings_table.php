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

        if (! Schema::hasColumn('bookings', 'unspecified_count')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->unsignedInteger('unspecified_count')->default(0)->after('child_count');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (Schema::hasColumn('bookings', 'unspecified_count')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('unspecified_count');
            });
        }
    }
};
