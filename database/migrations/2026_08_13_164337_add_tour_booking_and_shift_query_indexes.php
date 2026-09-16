<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! $this->indexExists('bookings', 'bookings_tour_id_status_is_waitlist_index')) {
                    $table->index(['tour_id', 'status', 'is_waitlist'], 'bookings_tour_id_status_is_waitlist_index');
                }

                if (! $this->indexExists('bookings', 'bookings_created_at_status_is_waitlist_index')) {
                    $table->index(['created_at', 'status', 'is_waitlist'], 'bookings_created_at_status_is_waitlist_index');
                }
            });
        }

        if (Schema::hasTable('tours')) {
            Schema::table('tours', function (Blueprint $table) {
                if (! $this->indexExists('tours', 'tours_tour_date_status_index')) {
                    $table->index(['tour_date', 'status'], 'tours_tour_date_status_index');
                }
            });
        }

        if (Schema::hasTable('work_shifts')) {
            Schema::table('work_shifts', function (Blueprint $table) {
                if (! $this->indexExists('work_shifts', 'work_shifts_shift_date_role_status_index')) {
                    $table->index(['shift_date', 'shift_role', 'status'], 'work_shifts_shift_date_role_status_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if ($this->indexExists('bookings', 'bookings_tour_id_status_is_waitlist_index')) {
                    $table->dropIndex('bookings_tour_id_status_is_waitlist_index');
                }

                if ($this->indexExists('bookings', 'bookings_created_at_status_is_waitlist_index')) {
                    $table->dropIndex('bookings_created_at_status_is_waitlist_index');
                }
            });
        }

        if (Schema::hasTable('tours')) {
            Schema::table('tours', function (Blueprint $table) {
                if ($this->indexExists('tours', 'tours_tour_date_status_index')) {
                    $table->dropIndex('tours_tour_date_status_index');
                }
            });
        }

        if (Schema::hasTable('work_shifts')) {
            Schema::table('work_shifts', function (Blueprint $table) {
                if ($this->indexExists('work_shifts', 'work_shifts_shift_date_role_status_index')) {
                    $table->dropIndex('work_shifts_shift_date_role_status_index');
                }
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return Schema::hasIndex($table, $index);
    }
};
