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

        if (! Schema::hasColumn('bookings', 'is_waitlist')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->boolean('is_waitlist')->default(false);
            });
        }

        if (! Schema::hasColumn('bookings', 'is_walk_in')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->boolean('is_walk_in')->default(false);
            });
        }

        if (! Schema::hasColumn('bookings', 'arrival_status')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('arrival_status', 32)->default('booked');
            });
        }

        if (! Schema::hasColumn('bookings', 'checked_in_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->timestamp('checked_in_at')->nullable();
            });
        }

        if (! Schema::hasColumn('bookings', 'reminder_sent_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->timestamp('reminder_sent_at')->nullable();
            });
        }

        if (! Schema::hasColumn('bookings', 'moved_from_tour_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('moved_from_tour_id')->nullable()->constrained('tours')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'moved_from_tour_id')) {
                $table->dropConstrainedForeignId('moved_from_tour_id');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'reminder_sent_at',
                'checked_in_at',
                'arrival_status',
                'is_walk_in',
                'is_waitlist',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
