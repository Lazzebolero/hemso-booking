<?php

namespace App\Services;

use App\Models\AudioDevice;
use App\Models\AudioGroup;
use App\Models\Loudspeaker;

class AudioFleetPlaybackService
{
    public function play(Loudspeaker $loudspeaker): void
    {
        $loudspeaker->update(['status' => true]);
    }

    public function stop(Loudspeaker $loudspeaker): void
    {
        $loudspeaker->update(['status' => false]);
    }

    public function playGroup(AudioGroup $group): int
    {
        return Loudspeaker::query()
            ->whereIn('device_id', $group->devices()->select('audio_devices.id'))
            ->whereNotNull('sound_id')
            ->update(['status' => true]);
    }

    public function stopGroup(AudioGroup $group): void
    {
        Loudspeaker::query()
            ->whereIn('device_id', $group->devices()->select('audio_devices.id'))
            ->update(['status' => false]);
    }

    public function stopDevice(AudioDevice $device): void
    {
        $device->loudspeakers()->update(['status' => false]);
    }

    public function stopAll(): void
    {
        Loudspeaker::query()->update(['status' => false]);
    }
}
