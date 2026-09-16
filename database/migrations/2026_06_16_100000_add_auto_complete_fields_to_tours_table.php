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

        Schema::table('tours', function (Blueprint $table) {
            if (! Schema::hasColumn('tours', 'baseline_end_time')) {
                $table->time('baseline_end_time')->nullable()->after('end_time');
            }

            if (! Schema::hasColumn('tours', 'auto_completed_at')) {
                $table->timestamp('auto_completed_at')->nullable()->after('ended_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tours')) {
            return;
        }

        Schema::table('tours', function (Blueprint $table) {
            if (Schema::hasColumn('tours', 'auto_completed_at')) {
                $table->dropColumn('auto_completed_at');
            }

            if (Schema::hasColumn('tours', 'baseline_end_time')) {
                $table->dropColumn('baseline_end_time');
            }
        });
    }
};
