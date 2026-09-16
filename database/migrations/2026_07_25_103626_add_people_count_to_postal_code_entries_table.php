<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('postal_code_entries')) {
            return;
        }

        if (Schema::hasColumn('postal_code_entries', 'people_count')) {
            return;
        }

        Schema::table('postal_code_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('people_count')->default(1)->after('postal_code');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('postal_code_entries')) {
            return;
        }

        if (! Schema::hasColumn('postal_code_entries', 'people_count')) {
            return;
        }

        Schema::table('postal_code_entries', function (Blueprint $table) {
            $table->dropColumn('people_count');
        });
    }
};
