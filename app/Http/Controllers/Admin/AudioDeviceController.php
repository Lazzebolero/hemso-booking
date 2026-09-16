<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioDevice;
use App\Models\AudioGroup;
use App\Models\Loudspeaker;
use App\Models\Sound;
use App\Services\AudioFleetPlaybackService;
use App\Support\AudioChannelSides;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AudioDeviceController extends Controller
{
    public function index(): View
    {
        $groups = AudioGroup::query()
            ->with(['devices.loudspeakers.sound'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $devices = AudioDevice::query()
            ->with(['group', 'loudspeakers.sound'])
            ->orderBy('id')
            ->get();

        $ungroupedDevices = $devices->whereNull('audio_group_id');

        return view('admin.audio.index', compact('groups', 'devices', 'ungroupedDevices'));
    }

    public function create(): View
    {
        $suggestedId = (int) (AudioDevice::query()->max('id') ?? 0) + 1;

        return view('admin.audio.devices.create', [
            'device' => new AudioDevice([
                'id' => $suggestedId,
                'is_active' => true,
            ]),
            'suggestedId' => $suggestedId,
            'groups' => $this->groupOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'min:1', 'max:65535', 'unique:audio_devices,id'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'audio_group_id' => ['nullable', 'integer', 'exists:audio_groups,id'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in(AudioChannelSides::keys())],
        ]);

        $channels = AudioChannelSides::normalize($data['channels']);
        unset($data['channels']);

        if ($channels === []) {
            return back()
                ->withInput()
                ->withErrors(['channels' => 'Välj minst en kanal (vänster eller höger).']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['audio_group_id'] = $data['audio_group_id'] ?? null;

        DB::transaction(function () use ($data, $channels): void {
            $device = AudioDevice::create($data);

            foreach ($channels as $side) {
                Loudspeaker::create([
                    'device_id' => $device->id,
                    'side' => $side,
                    'status' => false,
                ]);
            }
        });

        return redirect()
            ->route('admin.audio.devices.show', $data['id'])
            ->with('success', 'Ljudenhet registrerad.');
    }

    public function show(AudioDevice $device): View
    {
        $device->load(['group', 'loudspeakers.sound']);
        $sounds = Sound::query()->orderBy('name')->get();
        $availableChannels = AudioChannelSides::availableOptions(
            $device->loudspeakers->pluck('side')->all(),
        );

        return view('admin.audio.devices.show', compact('device', 'sounds', 'availableChannels'));
    }

    public function edit(AudioDevice $device): View
    {
        return view('admin.audio.devices.edit', [
            'device' => $device,
            'groups' => $this->groupOptions(),
        ]);
    }

    public function update(Request $request, AudioDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'audio_group_id' => ['nullable', 'integer', 'exists:audio_groups,id'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['audio_group_id'] = $data['audio_group_id'] ?? null;
        $device->update($data);

        return redirect()
            ->route('admin.audio.devices.show', $device)
            ->with('success', 'Ljudenhet uppdaterad.');
    }

    public function destroy(AudioDevice $device): RedirectResponse
    {
        $device->delete();

        return redirect()
            ->route('admin.audio.index')
            ->with('success', 'Ljudenhet borttagen.');
    }

    public function storeChannel(Request $request, AudioDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'side' => [
                'required',
                'string',
                Rule::in(AudioChannelSides::keys()),
                Rule::unique('loudspeakers', 'side')->where(fn ($query) => $query->where('device_id', $device->id)),
            ],
            'sound_id' => ['nullable', 'integer', 'exists:sounds,id'],
        ]);

        Loudspeaker::create([
            'device_id' => $device->id,
            'side' => $data['side'],
            'sound_id' => $data['sound_id'] ?? null,
            'status' => false,
        ]);

        return back()->with('success', 'Kanal tillagd.');
    }

    public function updateChannel(Request $request, Loudspeaker $loudspeaker): RedirectResponse
    {
        $data = $request->validate([
            'sound_id' => ['nullable', 'integer', 'exists:sounds,id'],
        ]);

        $loudspeaker->update([
            'sound_id' => $data['sound_id'] ?? null,
        ]);

        return back()->with('success', 'Kanal uppdaterad.');
    }

    public function destroyChannel(Loudspeaker $loudspeaker): RedirectResponse
    {
        $loudspeaker->delete();

        return back()->with('success', 'Kanal borttagen.');
    }

    public function playChannel(Loudspeaker $loudspeaker, AudioFleetPlaybackService $playback): RedirectResponse
    {
        if (! $loudspeaker->sound_id) {
            return back()->withErrors(['sound' => 'Välj ett ljud innan uppspelning.']);
        }

        $playback->play($loudspeaker);

        return back()->with('success', 'Uppspelning startad.');
    }

    public function stopChannel(Loudspeaker $loudspeaker, AudioFleetPlaybackService $playback): RedirectResponse
    {
        $playback->stop($loudspeaker);

        return back()->with('success', 'Uppspelning stoppad.');
    }

    public function stopDevice(AudioDevice $device, AudioFleetPlaybackService $playback): RedirectResponse
    {
        $playback->stopDevice($device);

        return back()->with('success', 'Alla kanaler stoppade på enheten.');
    }

    public function stopAll(AudioFleetPlaybackService $playback): RedirectResponse
    {
        $playback->stopAll();

        return back()->with('success', 'Alla enheter stoppade.');
    }

    public function setup(AudioDevice $device): View
    {
        $credentials = $this->piDatabaseCredentials();

        return view('admin.audio.devices.setup', [
            'device' => $device,
            'dbHost' => $credentials['host'],
            'dbName' => $credentials['database'],
            'dbUser' => $credentials['username'] !== '' ? $credentials['username'] : '(saknas)',
            'credentialsConfigured' => $this->piDatabaseCredentialsAreConfigured($credentials),
        ]);
    }

    public function downloadConfig(AudioDevice $device): StreamedResponse|RedirectResponse
    {
        $credentials = $this->piDatabaseCredentials();

        if (! $this->piDatabaseCredentialsAreConfigured($credentials)) {
            return back()->withErrors([
                'pi' => 'Sätt AUDIO_FLEET_PI_DB_USERNAME och AUDIO_FLEET_PI_DB_PASSWORD i .env (dedikerad read-only MySQL-användare). Appens DB-lösenord används inte.',
            ]);
        }

        $contents = $this->bunkerberryConfig($device, $credentials);

        return response()->streamDownload(function () use ($contents): void {
            echo $contents;
        }, 'bunkerberry.conf', [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /** @return Collection<int, AudioGroup> */
    private function groupOptions()
    {
        return AudioGroup::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{host: string, database: string, username: string, password: string}
     */
    private function piDatabaseCredentials(): array
    {
        return [
            'host' => (string) config('audio_fleet.pi_database.host'),
            'database' => (string) config('audio_fleet.pi_database.database'),
            'username' => (string) config('audio_fleet.pi_database.username'),
            'password' => (string) config('audio_fleet.pi_database.password'),
        ];
    }

    /**
     * @param  array{host: string, database: string, username: string, password: string}  $credentials
     */
    private function piDatabaseCredentialsAreConfigured(array $credentials): bool
    {
        return $credentials['username'] !== '' && $credentials['password'] !== '';
    }

    /**
     * @param  array{host: string, database: string, username: string, password: string}  $credentials
     */
    private function bunkerberryConfig(AudioDevice $device, array $credentials): string
    {
        return <<<INI
; Spara som bunkerberry.conf på SD-kortets boot-partition
[device]
device_id = {$device->id}

[database]
host = {$credentials['host']}
user = {$credentials['username']}
password = {$credentials['password']}
database = {$credentials['database']}
INI;
    }
}
