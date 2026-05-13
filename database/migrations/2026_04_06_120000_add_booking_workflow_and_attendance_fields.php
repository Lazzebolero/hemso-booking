<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            if (!Schema::hasColumn('tours', 'tour_type')) {
                $table->string('tour_type')->nullable()->after('title');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'arrival_status')) $table->string('arrival_status')->default('booked')->after('status');
            if (!Schema::hasColumn('bookings', 'is_waitlist')) $table->boolean('is_waitlist')->default(false)->after('arrival_status');
            if (!Schema::hasColumn('bookings', 'is_walk_in')) $table->boolean('is_walk_in')->default(false)->after('is_waitlist');
            if (!Schema::hasColumn('bookings', 'moved_from_tour_id')) $table->foreignId('moved_from_tour_id')->nullable()->after('tour_id')->constrained('tours')->nullOnDelete();
            if (!Schema::hasColumn('bookings', 'checked_in_at')) $table->timestamp('checked_in_at')->nullable()->after('is_walk_in');
            if (!Schema::hasColumn('bookings', 'actual_men_count')) $table->unsignedInteger('actual_men_count')->default(0)->after('child_count');
            if (!Schema::hasColumn('bookings', 'actual_women_count')) $table->unsignedInteger('actual_women_count')->default(0)->after('actual_men_count');
            if (!Schema::hasColumn('bookings', 'actual_youth_count')) $table->unsignedInteger('actual_youth_count')->default(0)->after('actual_women_count');
            if (!Schema::hasColumn('bookings', 'actual_child_count')) $table->unsignedInteger('actual_child_count')->default(0)->after('actual_youth_count');
            if (!Schema::hasColumn('bookings', 'actual_total_count')) $table->unsignedInteger('actual_total_count')->default(0)->after('actual_child_count');
            if (!Schema::hasColumn('bookings', 'duplicate_warning')) $table->boolean('duplicate_warning')->default(false)->after('actual_total_count');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['duplicate_warning','actual_total_count','actual_child_count','actual_youth_count','actual_women_count','actual_men_count','checked_in_at','is_walk_in','is_waitlist','arrival_status'] as $column) {
                if (Schema::hasColumn('bookings', $column)) $table->dropColumn($column);
            }
            if (Schema::hasColumn('bookings', 'moved_from_tour_id')) $table->dropConstrainedForeignId('moved_from_tour_id');
        });

        Schema::table('tours', function (Blueprint $table) {
            if (Schema::hasColumn('tours', 'tour_type')) $table->dropColumn('tour_type');
        });
    }
};
