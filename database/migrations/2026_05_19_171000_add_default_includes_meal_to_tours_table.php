<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tours')) {
            return;
        }

        if (! Schema::hasColumn('tours', 'default_includes_meal')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->boolean('default_includes_meal')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tours')) {
            return;
        }

        if (Schema::hasColumn('tours', 'default_includes_meal')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->dropColumn('default_includes_meal');
            });
        }
    }
};
