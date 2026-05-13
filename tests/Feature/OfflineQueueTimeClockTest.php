<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OfflineQueueTimeClockTest extends TestCase
{
    public function test_clock_in_accepts_recent_client_timestamp(): void
    {
        $user = User::factory()->create();

        $occurredAt = Carbon::now()->subMinutes(2);

        $this->actingAs($user)
            ->post(route('time.clock-in'), [
                'client_occurred_at' => $occurredAt->toISOString(),
                'client_tz' => 'Europe/Stockholm',
            ])
            ->assertRedirect(route('time.index'))
            ->assertSessionHas('success');

        $entry = TimeEntry::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame($occurredAt->format('Y-m-d'), $entry->work_date->format('Y-m-d'));

        $expectedInstant = Carbon::parse($occurredAt->toISOString())->timezone(config('app.timezone'));
        $this->assertSame(
            $expectedInstant->format('Y-m-d H:i'),
            $entry->start_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            sprintf(
                'entry=%s expected=%s',
                $entry->start_at?->toIso8601String() ?? 'null',
                $expectedInstant->toIso8601String()
            )
        );
    }
}

