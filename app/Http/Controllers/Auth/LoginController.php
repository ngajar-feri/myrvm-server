<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Generate Sanctum API token for dashboard API calls
            $user = Auth::user();
            $token = $user->createToken('dashboard-token')->plainTextToken;
            $request->session()->put('api_token', $token);

            // Log successful login
            ActivityLog::log('Auth', 'Login', "User {$user->name} ({$user->email}) logged in successfully", $user->id);

            return redirect()->intended(route('dashboard'));
        }

        // Log failed login attempt
        ActivityLog::create([
            'user_id' => null,
            'module' => 'Auth',
            'action' => 'Error',
            'description' => "Failed login attempt for email: {$request->email}",
            'ip_address' => $request->ip(),
        ]);

        return back()->withErrors([
            'email' => 'Email atau password tidak valid.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        // Log logout
        if ($user) {
            ActivityLog::log('Auth', 'Logout', "User {$user->name} ({$user->email}) logged out");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('landing');
    }
}
