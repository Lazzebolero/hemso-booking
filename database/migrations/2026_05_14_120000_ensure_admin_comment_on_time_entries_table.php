<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Earlier installs may have run an empty `add_admin_comment` migration; ensure the column exists.
     */
    public function up(): void
    {
        if (Schema::hasColumn('time_entries', 'admin_comment')) {
            return;
        }

        Schema::table('time_entries', function (Blueprint $table) {
            $table->text('admin_comment')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('time_entries', 'admin_comment')) {
            return;
        }

        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('admin_comment');
        });
    }
};
