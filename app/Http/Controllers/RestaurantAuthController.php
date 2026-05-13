<?php

namespace App\Http\Controllers;

use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestaurantAuthController extends Controller
{
    public function showLogin()
    {
        return view('restaurant.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ange e-postadress.',
            'password.required' => 'Ange lösenord.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'Fel e-postadress eller lösenord.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user()->loadMissing('roles');

        if (! $user->hasRole(Roles::RESTAURANT)) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Detta konto har inte åtkomst till restaurangvyn.',
            ]);
        }

        session(['active_role' => Roles::RESTAURANT]);

        return redirect()->route('restaurant.kiosk');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('restaurant.login');
    }
}