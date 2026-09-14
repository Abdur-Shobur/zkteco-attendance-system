<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email', 'remember'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return redirect()->back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput($request->only('email', 'remember'));
        }

        $user = Auth::user();

        if (!$user->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->back()
                ->withErrors(['email' => 'You do not have admin access to this panel.'])
                ->withInput($request->only('email', 'remember'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('attendance.index'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
