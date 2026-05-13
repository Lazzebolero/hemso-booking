<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:admin,host,guide',
            'is_active' => 'required|boolean',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        LogService::log('user', $user->id, 'created', null, $user->only(['name','email','role','is_active']), 'Skapade användare');
        return redirect()->route('admin.users.index')->with('success', 'Användare skapad.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $old = $user->only(['name','email','role','is_active']);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:admin,host,guide',
            'is_active' => 'required|boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        LogService::log('user', $user->id, 'updated', $old, $user->fresh()->only(['name','email','role','is_active']), 'Uppdaterade användare');
        return redirect()->route('admin.users.index')->with('success', 'Användare uppdaterad.');
    }

    public function destroy(User $user)
    {
        $old = $user->only(['name','email','role','is_active']);
        $user->delete();
        LogService::log('user', $user->id, 'deleted', $old, null, 'Tog bort användare');
        return back()->with('success', 'Användare borttagen.');
    }
}
