<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSeederRunsTest extends TestCase
{
    public function test_migrations_and_database_seeder_complete_without_errors(): void
    {
        Artisan::call('db:seed', ['--no-interaction' => true]);

        $this->assertTrue(Schema::hasColumn('bookings', 'is_waitlist'));
        $this->assertTrue(Schema::hasTable('booking_language'));
        $this->assertTrue(Schema::hasTable('tour_types'));
        $this->assertTrue(Schema::hasTable('tour_booking_pages'));
        $this->assertTrue(Schema::hasColumn('tours', 'tour_type_id'));
        $this->assertTrue(Schema::hasTable('settings'));
        $this->assertTrue(Schema::hasTable('system_messages'));
        $this->assertTrue(Schema::hasTable('system_message_user'));

        $this->assertTrue(Schema::hasTable('report_categories'));
        $this->assertTrue(Schema::hasTable('report_priorities'));
        $this->assertTrue(Schema::hasTable('report_statuses'));
        $this->assertTrue(Schema::hasTable('staff_documents'));

        $this->assertGreaterThan(0, User::query()->count());
        $this->assertGreaterThan(0, Tour::query()->count());
    }
}
