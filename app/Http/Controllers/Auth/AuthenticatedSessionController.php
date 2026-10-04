<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
     * Authenticate an Admin/Customer or Staff/Technician account.
     */
    public function store(Request $request): RedirectResponse
    {
        $loginType = $request->input(
            'login_type',
            'account'
        );

        $request->validate([
            'login_type' => [
                'nullable',
                Rule::in([
                    'account',
                    'employee',
                ]),
            ],
        ]);

        if ($loginType === 'employee') {
            return $this->storeEmployeeLogin(
                $request
            );
        }

        return $this->storeAccountLogin(
            $request
        );
    }

    /**
     * Authenticate Admin and Customer accounts by email.
     */
    private function storeAccountLogin(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'email' => [
                    'bail',
                    'required',
                    'email',
                ],

                'password' => [
                    'bail',
                    'required',
                    'string',
                    'min:8',
                ],
            ],
            [
                'email.required' =>
                    'Please enter your email address.',

                'email.email' =>
                    'Please enter a valid email address.',

                'password.required' =>
                    'Please enter your password.',

                'password.min' =>
                    'Password must be at least 8 characters.',
            ]
        );

        $email = mb_strtolower(
            trim(
                $validated['email']
            )
        );

        $user = User::query()
            ->where(
                'email',
                $email
            )
            ->whereIn(
                'role',
                [
                    'admin',
                    'customer',
                ]
            )
            ->first();

        if (
            ! $user instanceof User
            || ! Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' =>
                    'The provided credentials are incorrect.',
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' =>
                    $user->account_status === 'pending_verification'
                        ? 'Your account is pending verification.'
                        : 'Your account is inactive.',
            ]);
        }

        Auth::login(
            $user,
            $request->boolean(
                'remember'
            )
        );

        $request->session()->regenerate();

        return $this->completeLogin(
            $request,
            $user,
            'email'
        );
    }

    /**
     * Authenticate Staff and Technician accounts by employee number.
     *
     * A pending employee who enters the correct temporary password is
     * routed to the verification-method chooser before any OTP is issued.
     */
    private function storeEmployeeLogin(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'employee_number' => [
                    'bail',
                    'required',
                    'string',
                    'max:30',
                ],

                'password' => [
                    'bail',
                    'required',
                    'string',
                    'min:8',
                ],
            ],
            [
                'employee_number.required' =>
                    'Please enter your employee number.',

                'password.required' =>
                    'Please enter your password.',

                'password.min' =>
                    'Password must be at least 8 characters.',
            ]
        );

        $employeeNumber = mb_strtoupper(
            trim(
                $validated['employee_number']
            )
        );

        $user = User::query()
            ->where(
                'employee_number',
                $employeeNumber
            )
            ->whereIn(
                'role',
                [
                    'staff',
                    'technician',
                ]
            )
            ->first();

        if (
            ! $user instanceof User
            || ! Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'employee_number' =>
                    'The provided employee credentials are incorrect.',
            ]);
        }

        if (
            $user->account_status
                === 'pending_verification'
            && $user->activation_completed_at === null
        ) {
            return $this->beginEmployeeActivation(
                $request,
                $user
            );
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'employee_number' =>
                    'Your employee account is inactive.',
            ]);
        }

        if (
            $user->activation_completed_at === null
        ) {
            throw ValidationException::withMessages([
                'employee_number' =>
                    'Your employee account has not completed activation. Please contact an administrator.',
            ]);
        }

        Auth::login(
            $user,
            $request->boolean(
                'remember'
            )
        );

        $request->session()->regenerate();

        return $this->completeLogin(
            $request,
            $user,
            'employee_number'
        );
    }

    /**
     * Bind first-time employee activation to this browser session and
     * send the employee to the verification-method chooser.
     */
    private function beginEmployeeActivation(
        Request $request,
        User $user
    ): RedirectResponse {
        if (
            filter_var(
                (string) $user->email,
                FILTER_VALIDATE_EMAIL
            ) === false
            && trim(
                (string) $user->phone
            ) === ''
        ) {
            throw ValidationException::withMessages([
                'employee_number' =>
                    'No email address or mobile number is assigned to this employee account. Please contact an administrator.',
            ]);
        }

        $request->session()->regenerate();

        $request->session()->forget(
            'employee.activation.challenge'
        );

        $request->session()->put(
            'employee.activation.user_id',
            $user->getKey()
        );

        return redirect()
            ->route(
                'employee.activation.channel'
            );
    }

    /**
     * Record login activity and redirect to the correct dashboard.
     */
    private function completeLogin(
        Request $request,
        User $user,
        string $loginMethod
    ): RedirectResponse {
        $routeName = match (
            $user->role
        ) {
            'admin',
            'staff' =>
                'dashboard',

            'technician' =>
                'technician.dashboard',

            'customer' =>
                'customer.dashboard',

            default =>
                null,
        };

        if ($routeName === null) {
            return $this->logoutUnknownRole(
                $request
            );
        }

        $this->activityLogger->record(
            action:
                'user.logged_in',

            actor: $user,
            target: $user,

            description:
                'Signed in to the Rincomm system.',

            metadata: [
                'role' =>
                    $user->role,

                'login_method' =>
                    $loginMethod,
            ],

            request: $request
        );

        return redirect()
            ->route(
                $routeName
            );
    }

    /**
     * Log the user out.
     */
    public function destroy(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        if ($user) {
            $this->activityLogger->record(
                action:
                    'user.logged_out',

                actor: $user,
                target: $user,

                description:
                    'Signed out of the Rincomm system.',

                metadata: [
                    'role' =>
                        $user->role,
                ],

                request: $request
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route(
                'home'
            );
    }

    /**
     * Safely handle an unknown role.
     */
    private function logoutUnknownRole(
        Request $request
    ): RedirectResponse {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route(
                'login'
            )
            ->withErrors([
                'email' =>
                    'Your account does not have a valid system role.',
            ]);
    }
}
