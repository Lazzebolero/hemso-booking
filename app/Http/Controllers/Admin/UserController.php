<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Role;
use App\Models\User;
use App\Services\LogService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $relations = ['roles'];

        if ($this->guideLanguagesEnabled()) {
            $relations[] = 'guideLanguages';
        }

        $users = User::with($relations)
            ->withoutProductionRoles()
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = $this->hemsoStaffRoleRecords();

        return view('admin.users.form', [
            'user' => new User,
            'roles' => $roles,
            'languages' => $this->activeLanguages(),
            'selectedGuideLanguageIds' => [],
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
            'password' => Hash::make($data['password'] ?? Str::random(32)),
        ]);

        $roleIds = Role::query()
            ->whereIn('slug', $data['roles'])
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);
        $this->syncGuideLanguages($user, $data['roles'], $data['guide_languages'] ?? []);

        LogService::log(
            'user',
            $user->id,
            'created',
            null,
            $user->fresh(['roles', 'guideLanguages'])->only([
                'name',
                'email',
                'phone',
                'is_active',
                'is_kiosk',
                'kiosk_target',
            ]) + [
                'roles' => $user->fresh('roles')->roles->pluck('slug')->all(),
                'guide_languages' => $user->fresh('guideLanguages')->guideLanguages->pluck('code')->all(),
            ],
            'Skapade användare'
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Användare skapad.');
    }

    public function edit(User $user): View
    {
        $this->ensureHemsoStaffUser($user);

        $roles = $this->hemsoStaffRoleRecords();

        return view('admin.users.form', [
            'user' => $user->load(array_values(array_filter([
                'roles',
                $this->guideLanguagesEnabled() ? 'guideLanguages' : null,
            ]))),
            'roles' => $roles,
            'languages' => $this->activeLanguages(),
            'selectedGuideLanguageIds' => old(
                'guide_languages',
                $user->guideLanguages->pluck('id')->map(fn ($id) => (string) $id)->all()
            ),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureHemsoStaffUser($user);

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
        $this->syncGuideLanguages($user, $data['roles'], $data['guide_languages'] ?? []);

        $fresh = $user->fresh(['roles', 'guideLanguages']);

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
                'guide_languages' => $fresh->guideLanguages->pluck('code')->all(),
            ],
            'Uppdaterade användare'
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Användare uppdaterad.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureHemsoStaffUser($user);

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

        if (! $isCreate && ! $request->filled('password')) {
            $request->merge([
                'password' => null,
                'password_confirmation' => null,
            ]);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'phone' => 'nullable|string|max:50',
            'roles' => 'required|array|min:1',
            'roles.*' => ['required', 'string', Rule::in(Roles::hemsoStaffRoles())],
            'guide_languages' => 'nullable|array',
            'guide_languages.*' => 'exists:languages,id',
            'is_active' => 'required|boolean',

            'is_kiosk' => 'nullable|boolean',
            'kiosk_target' => [
                'nullable',
                Rule::in(['restaurant-board']),
            ],
        ];

        if ($isCreate || $request->filled('password')) {
            if ($isCreate && $this->isScheduleOnlyRoleSelection($request->input('roles', [])) && ! $request->filled('password')) {
                // Trainee/elev behöver inget lösenord vid skapande.
            } else {
                $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            }
        }

        $data = $request->validate($rules);

        if ($this->isScheduleOnlyRoleSelection($data['roles'] ?? [])) {
            $data['is_active'] = false;
        }

        return $data;
    }

    private function ensureHemsoStaffUser(User $user): void
    {
        abort_if($user->hasProductionAccess(), 404);
    }

    /**
     * @return Collection<int, Role>
     */
    private function hemsoStaffRoleRecords(): Collection
    {
        return Role::query()
            ->whereIn('slug', Roles::hemsoStaffRoles())
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<string>  $roleSlugs
     */
    private function isScheduleOnlyRoleSelection(array $roleSlugs): bool
    {
        $roleSlugs = array_values(array_filter($roleSlugs));

        return $roleSlugs !== [] && array_diff($roleSlugs, Roles::scheduleOnlyRoles()) === [];
    }

    /**
     * @return Collection<int, Language>
     */
    private function activeLanguages()
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<string>  $roleSlugs
     * @param  list<int|string>  $languageIds
     */
    private function syncGuideLanguages(User $user, array $roleSlugs, array $languageIds): void
    {
        if (! $this->guideLanguagesEnabled()) {
            return;
        }

        if (! in_array(Roles::GUIDE, $roleSlugs, true)) {
            $user->guideLanguages()->detach();

            return;
        }

        $user->guideLanguages()->sync(array_map('intval', $languageIds));
    }

    private function guideLanguagesEnabled(): bool
    {
        return Schema::hasTable('guide_language');
    }
}
