<?php

namespace Tests\Feature;

use App\Mail\BookingInvoiceRequestMail;
use App\Models\Booking;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingToBeInvoicedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::query()->updateOrCreate(
            ['key' => 'economics_notification_email'],
            ['value' => 'ekonomi@hemso.test'],
        );
    }

    public function test_admin_can_create_booking_marked_to_be_invoiced(): void
    {
        Mail::fake();

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Företagsgrupp AB',
                'contact_name' => 'Anna Ekonomi',
                'men_count' => 4,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'to_be_invoiced' => '1',
            ])
            ->assertRedirect(route('admin.bookings.index'))
            ->assertSessionHas('success');

        $booking = Booking::query()->where('booking_name', 'Företagsgrupp AB')->first();

        $this->assertNotNull($booking);
        $this->assertTrue($booking->to_be_invoiced);
        $this->assertSame('Faktureras', $booking->invoiceLabel());

        Mail::assertSent(BookingInvoiceRequestMail::class, function (BookingInvoiceRequestMail $mail) use ($booking): bool {
            return $mail->hasTo('ekonomi@hemso.test')
                && $mail->booking->is($booking);
        });
    }

    public function test_booking_defaults_to_not_be_invoiced(): void
    {
        Mail::fake();

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Privat grupp',
                'men_count' => 2,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.bookings.index'));

        $booking = Booking::query()->where('booking_name', 'Privat grupp')->first();

        $this->assertNotNull($booking);
        $this->assertFalse($booking->to_be_invoiced);

        Mail::assertNothingSent();
    }

    public function test_no_economics_email_is_sent_when_address_is_missing(): void
    {
        Mail::fake();

        Setting::query()->where('key', 'economics_notification_email')->delete();

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Utan ekonomi-adress',
                'men_count' => 1,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'to_be_invoiced' => '1',
            ])
            ->assertRedirect(route('admin.bookings.index'));

        $this->assertTrue(Booking::query()->where('booking_name', 'Utan ekonomi-adress')->value('to_be_invoiced'));

        Mail::assertNothingSent();
    }

    public function test_admin_can_update_booking_invoice_flag_without_sending_email(): void
    {
        Mail::fake();

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Uppdatera faktura',
            'men_count' => 1,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 1,
            'status' => 'confirmed',
            'to_be_invoiced' => false,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.bookings.update', $booking), [
                'tour_id' => $tour->id,
                'booking_name' => 'Uppdatera faktura',
                'men_count' => 1,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'to_be_invoiced' => '1',
            ])
            ->assertRedirect(route('admin.bookings.index'));

        $this->assertTrue($booking->fresh()->to_be_invoiced);

        Mail::assertNothingSent();
    }

    public function test_booking_form_shows_invoice_checkbox(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.create', ['tour_id' => $tour->id]))
            ->assertOk()
            ->assertSee('Faktureras', false)
            ->assertSee('to_be_invoiced', false);

        $tourType = $tour->tourType ?? TourType::query()->firstOrFail();
        $tourType->update(['include_in_booking_sequence' => true]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.quick-create'))
            ->assertOk()
            ->assertSee('Faktureras', false)
            ->assertSee('quick_to_be_invoiced', false);
    }

    public function test_quick_booking_sequence_can_mark_booking_to_be_invoiced(): void
    {
        Mail::fake();

        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();
        $tourType->update(['include_in_booking_sequence' => true]);

        $tour = Tour::query()->create([
            'title' => 'Sekvens faktura',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.quick-store'), [
                'tour_id' => $tour->id,
                'unspecified_count' => 5,
                'to_be_invoiced' => '1',
            ])
            ->assertRedirect(route('admin.bookings.quick-create'));

        $booking = Booking::query()->where('tour_id', $tour->id)->first();

        $this->assertNotNull($booking);
        $this->assertTrue($booking->to_be_invoiced);

        Mail::assertSent(BookingInvoiceRequestMail::class, function (BookingInvoiceRequestMail $mail) use ($booking): bool {
            return $mail->hasTo('ekonomi@hemso.test')
                && $mail->booking->is($booking);
        });
    }

    public function test_quick_booking_succeeds_when_invoice_email_fails(): void
    {
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP failed'));

        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();
        $tourType->update(['include_in_booking_sequence' => true]);

        $tour = Tour::query()->create([
            'title' => 'Sekvens faktura fel',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.quick-store'), [
                'tour_id' => $tour->id,
                'unspecified_count' => 3,
                'to_be_invoiced' => '1',
            ])
            ->assertRedirect(route('admin.bookings.quick-create'))
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $booking = Booking::query()->where('tour_id', $tour->id)->first();

        $this->assertNotNull($booking);
        $this->assertTrue($booking->to_be_invoiced);
    }

    public function test_invoice_bookings_are_marked_and_searchable_on_index(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Företag att fakturera',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
            'to_be_invoiced' => true,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Privat grupp utan faktura',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
            'to_be_invoiced' => false,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('Företag att fakturera', false)
            ->assertSee('Faktureras', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.index', ['to_be_invoiced_only' => 1]))
            ->assertOk()
            ->assertSee('Företag att fakturera', false)
            ->assertDontSee('Privat grupp utan faktura', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.index', ['q' => 'faktureras']))
            ->assertOk()
            ->assertSee('Företag att fakturera', false)
            ->assertDontSee('Privat grupp utan faktura', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Företag att fakturera', false)
            ->assertSee('Faktureras', false);
    }

    public function test_booking_invoice_mail_renders(): void
    {
        $tour = $this->tour();
        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Render test',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
            'to_be_invoiced' => true,
            'includes_meal' => true,
        ]);

        $html = (new BookingInvoiceRequestMail($booking->fresh(['tour.tourType', 'languages'])))->render();

        $this->assertStringContainsString('Render test', $html);
        $this->assertStringContainsString('faktureras', $html);
        $this->assertStringContainsString('Med mat', $html);
    }

    public function test_admin_can_save_economics_notification_email_in_settings(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.settings.update'), [
                'default_tour_capacity' => 25,
                'timezone' => 'Europe/Stockholm',
                'staffing_goal_guides_weekday' => 2,
                'staffing_goal_guides_weekend' => 3,
                'staffing_goal_hosts' => 1,
                'staffing_goal_kock' => 1,
                'staffing_goal_kallskank' => 0,
                'staffing_goal_kassa' => 1,
                'staffing_goal_disk' => 0,
                'staffing_goal_glassbar' => 0,
                'staffing_goal_servering' => 1,
                'economics_notification_email' => 'ny-ekonomi@hemso.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            'ny-ekonomi@hemso.test',
            Setting::query()->where('key', 'economics_notification_email')->value('value'),
        );
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function tour(): Tour
    {
        return Tour::query()->create([
            'title' => 'Fakturatest tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);
    }
}
