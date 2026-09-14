<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

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

        $routeName = match ($user->role) {
            'admin', 'staff' => 'dashboard',
            'technician' => 'technician.dashboard',
            'customer' => 'customer.dashboard',
            default => null,
        };

        if ($routeName === null) {
            return $this->logoutUnknownRole($request);
        }

        $this->activityLogger->record(
            action: 'user.logged_in',
            actor: $user,
            target: $user,
            description: 'Signed in to the Rincomm system.',
            metadata: [
                'role' => $user->role,
            ],
            request: $request
        );

        return redirect()->route($routeName);
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->activityLogger->record(
                action: 'user.logged_out',
                actor: $user,
                target: $user,
                description: 'Signed out of the Rincomm system.',
                metadata: [
                    'role' => $user->role,
                ],
                request: $request
            );
        }

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
