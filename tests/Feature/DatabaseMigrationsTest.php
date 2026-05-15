<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigrationsTest extends TestCase
{
    public function test_required_application_tables_exist_after_migrations(): void
    {
        $required = [
            'users',
            'roles',
            'role_user',
            'languages',
            'tours',
            'bookings',
            'booking_language',
            'work_shifts',
            'time_entries',
            'time_entry_audits',
            'locked_payroll_periods',
            'visitor_dogs',
            'facility_reports',
            'report_categories',
            'report_priorities',
            'report_statuses',
            'report_locations',
            'report_options',
            'settings',
            'system_messages',
            'system_message_user',
            'staff_documents',
            'activity_logs',
            'tour_types',
            'tour_booking_pages',
        ];

        $missing = array_values(array_filter(
            $required,
            static fn (string $table): bool => ! Schema::hasTable($table),
        ));

        $this->assertSame(
            [],
            $missing,
            'Migrations did not create expected tables: '.implode(', ', $missing),
        );
    }
}
