<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login page.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(
    [
        'email' => ['bail', 'required', 'email'],
        'password' => ['bail', 'required', 'string', 'min:8'],
    ],
    [
        'email.required' => 'Please enter your email address.',
        'email.email' => 'Please enter a valid email address.',

        'password.required' => 'Please enter your password.',
        'password.min' => 'Password must be at least 8 characters.',
    ]
);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->isActive()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your account is inactive.',
            ]);
        }

        return match ($user->role) {
    'admin', 'staff' => redirect()->route('dashboard'),
    'technician' => redirect()->route('technician.dashboard'),
    'customer' => redirect()->route('customer.dashboard'),
    default => $this->logoutUnknownRole($request),
};
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Safely handle an unknown role.
     */
    private function logoutUnknownRole(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'Your account does not have a valid system role.',
            ]);
    }
}