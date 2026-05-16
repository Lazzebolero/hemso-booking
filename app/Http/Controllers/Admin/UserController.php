<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function exportContactsCsv(): StreamedResponse
    {
        $filename = 'hemso-personal-kontakter-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Name', 'E-mail Address', 'Phone', 'Categories', 'Notes']);

            User::query()
                ->with('roles')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->orderBy('name')
                ->each(function (User $user) use ($handle): void {
                    $roleNames = $user->roles
                        ->pluck('name')
                        ->filter()
                        ->values()
                        ->implode(', ');

                    fputcsv($handle, [
                        $user->name,
                        $user->email,
                        $user->phone ?? '',
                        $roleNames,
                        ($user->is_active ? 'Aktiv' : 'Inaktiv').($roleNames !== '' ? '; Roller: '.$roleNames : ''),
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): View
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('admin.users.form', [
            'user' => new User,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_kiosk' => (bool) ($data['is_kiosk'] ?? false),
            'kiosk_target' => ! empty($data['is_kiosk']) ? ($data['kiosk_target'] ?? null) : null,
            'password' => Hash::make($data['password']),
        ]);

        $roleIds = Role::query()
            ->whereIn('slug', $data['roles'])
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);

        LogService::log(
            'user',
            $user->id,
            'created',
            null,
            $user->fresh('roles')->only([
                'name',
                'email',
                'phone',
                'is_active',
                'is_kiosk',
                'kiosk_target',
            ]) + [
                'roles' => $user->fresh('roles')->roles->pluck('slug')->all(),
            ],
            'Skapade användare'
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Användare skapad.');
    }

    public function edit(User $user): View
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('admin.users.form', [
            'user' => $user->load('roles'),
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $old = $user->load('roles')->only([
            'name',
            'email',
            'phone',
            'is_active',
            'is_kiosk',
            'kiosk_target',
        ]) + [
            'roles' => $user->roles->pluck('slug')->all(),
        ];

        $data = $this->validated($request, false, $user);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_kiosk' => (bool) ($data['is_kiosk'] ?? false),
            'kiosk_target' => ! empty($data['is_kiosk']) ? ($data['kiosk_target'] ?? null) : null,
        ]);

        if (! empty($data['password'])) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        $roleIds = Role::query()
            ->whereIn('slug', $data['roles'])
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);

        $fresh = $user->fresh('roles');

        LogService::log(
            'user',
            $user->id,
            'updated',
            $old,
            $fresh->only([
                'name',
                'email',
                'phone',
                'is_active',
                'is_kiosk',
                'kiosk_target',
            ]) + [
                'roles' => $fresh->roles->pluck('slug')->all(),
            ],
            'Uppdaterade användare'
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Användare uppdaterad.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $old = $user->load('roles')->only([
            'name',
            'email',
            'phone',
            'is_active',
            'is_kiosk',
            'kiosk_target',
        ]) + [
            'roles' => $user->roles->pluck('slug')->all(),
        ];

        $userId = $user->id;
        $user->roles()->detach();
        $user->delete();

        LogService::log(
            'user',
            $userId,
            'deleted',
            $old,
            null,
            'Tog bort användare'
        );

        return back()->with('success', 'Användare borttagen.');
    }

    private function validated(Request $request, bool $isCreate, ?User $user = null): array
    {
        $emailRule = 'required|email|unique:users,email';
        if (! $isCreate && $user) {
            $emailRule .= ','.$user->id;
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'phone' => 'nullable|string|max:50',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,slug',
            'is_active' => 'required|boolean',

            'is_kiosk' => 'nullable|boolean',
            'kiosk_target' => [
                'nullable',
                Rule::in(['restaurant-board']),
            ],

            'password' => $isCreate
                ? 'required|string|min:8|confirmed'
                : 'nullable|string|min:8|confirmed',
        ]);
    }
}
