<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    private function portalRedirect(): string
    {
        $user = Auth::user();
        if ($user->hasRole('parent'))  return route('portal.parent.dashboard');
        if ($user->hasRole('student')) return route('portal.student.dashboard');
        return route('dashboard');
    }

    private function roleLabel(?\App\Models\User $user): string
    {
        if (!$user) return '';
        $role = $user->getRoleNames()->first();
        return $role ? ucwords(str_replace('_', ' ', $role)) : '';
    }

    public function landing()
    {
        return view('splash');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $rawInput = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $throttleKey = Str::transliterate(Str::lower($rawInput).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->withInput($request->only('email'));
        }

        // Look up user by email (case-insensitive for PostgreSQL), username (Employee ID), or mobile
        $cleanMobile = preg_replace('/[^0-9]/', '', $rawInput);
        $user = User::where(function ($q) use ($rawInput, $cleanMobile) {
            $q->whereRaw('LOWER(email) = ?', [strtolower($rawInput)])
              ->orWhereRaw('LOWER(username) = ?', [strtolower($rawInput)])
              ->orWhere('mobile', $rawInput);

            if (!empty($cleanMobile) && strlen($cleanMobile) >= 10) {
                $q->orWhere('mobile', substr($cleanMobile, -10));
            }
        })->first();

        $attemptSuccess = false;

        if ($user) {
            // Standard cryptographic password hash verification
            if (Hash::check($password, $user->password)) {
                $attemptSuccess = true;
            }
        }

        if ($attemptSuccess && $user) {
            // Check if user is an administrator requiring PIN verification
            $isAdminRole = $user->hasAnyRole(['super_admin', 'admin']) || strtolower($user->email) === 'admin@schoolerp.in';

            if ($isAdminRole) {
                $submittedPin = trim((string) $request->input('admin_pin', ''));
                $devPin = config('auth.admin_dev_pin', '1122');
                $isPinValid = false;

                // 1. Strict verification against hashed database PIN
                if (!empty($user->admin_pin) && Hash::check($submittedPin, $user->admin_pin)) {
                    $isPinValid = true;
                }
                // 2. Fallback strictly restricted to local and testing environments
                elseif (app()->environment('local', 'testing') && !empty($devPin) && $submittedPin === $devPin) {
                    $isPinValid = true;
                }

                if (!$isPinValid) {
                    RateLimiter::hit($throttleKey, 60);
                    return back()->withErrors([
                        'admin_pin' => empty($submittedPin) 
                            ? 'Admin Security PIN is required for administrator accounts.' 
                            : 'Invalid Admin Security PIN. Please try again.'
                    ])->withInput($request->only('email'));
                }
            }

            RateLimiter::clear($throttleKey);

            if (!$user->is_active) {
                return back()->withErrors(['email' => 'Your account has been deactivated.']);
            }

            Auth::login($user, $request->boolean('remember'));

            $user->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            if ($user->must_change_password) {
                return redirect()->route('password.force_change')
                    ->with('warning', 'Temporary credentials detected. Please change your password to continue.');
            }

            return redirect()->intended($this->portalRedirect());
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors(['email' => 'Invalid credentials. Please try again.'])->withInput($request->only('email'));
    }

    public function showForceChangePassword()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.force-change-password', compact('user'));
    }

    public function forceChangePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'current_password' => ['nullable', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        // If user submitted current password, verify it
        if ($request->filled('current_password')) {
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
            }
        }

        $user->update([
            'password'             => Hash::make($request->input('password')),
            'must_change_password' => false,
        ]);

        return redirect($this->portalRedirect())->with('success', 'Password successfully updated. Welcome to School ERP!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
