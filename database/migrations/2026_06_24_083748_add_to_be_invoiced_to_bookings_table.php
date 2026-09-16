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

        if (! Schema::hasColumn('bookings', 'to_be_invoiced')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->boolean('to_be_invoiced')->default(false)->after('includes_meal');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (Schema::hasColumn('bookings', 'to_be_invoiced')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('to_be_invoiced');
            });
        }
    }
};
