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
            if (! Schema::hasColumn('tours', 'booked_total_at_start')) {
                $table->unsignedInteger('booked_total_at_start')->nullable();
            }

            if (! Schema::hasColumn('tours', 'actual_total_at_start')) {
                $table->unsignedInteger('actual_total_at_start')->nullable();
            }

            if (! Schema::hasColumn('tours', 'headcount_adjusted_at')) {
                $table->timestamp('headcount_adjusted_at')->nullable();
            }

            if (! Schema::hasColumn('tours', 'headcount_adjusted_by')) {
                $table->foreignId('headcount_adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tours')) {
            return;
        }

        Schema::table('tours', function (Blueprint $table) {
            if (Schema::hasColumn('tours', 'headcount_adjusted_by')) {
                $table->dropConstrainedForeignId('headcount_adjusted_by');
            }

            foreach (['headcount_adjusted_at', 'actual_total_at_start', 'booked_total_at_start'] as $column) {
                if (Schema::hasColumn('tours', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
