<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Display the registration page.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new customer/applicant account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'first_name' => [
                    'bail',
                    'required',
                    'string',
                    'max:100',
                ],
                'last_name' => [
                    'bail',
                    'required',
                    'string',
                    'max:100',
                ],
                'email' => [
                    'bail',
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],
                'password' => [
                    'bail',
                    'required',
                    'string',
                    'min:8',
                ],
                'password_confirmation' => [
                    'bail',
                    'required',
                    'string',
                    'same:password',
                ],
                'application_flow' => [
                    'nullable',
                    'in:1',
                ],
            ],
            [
                'first_name.required' => 'Please enter your first name.',
                'last_name.required' => 'Please enter your last name.',

                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'An account with this email address already exists.',

                'password.required' => 'Password must not be empty.',
                'password.min' => 'Password must be at least 8 characters.',
                'password_confirmation.required' => 'Please confirm your password.',
                'password_confirmation.same' => 'Password confirmation does not match.',
            ]
        );

        $isApplicationFlow =
            ($validated['application_flow'] ?? null) === '1' &&
            $request->session()->has('service_application.coverage') &&
            $request->session()->has('service_application.plan_id');

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => trim(
                    $validated['first_name'] . ' ' . $validated['last_name']
                ),
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            // Public registration can only create customer/applicant accounts.
            $user->role = 'customer';
            $user->account_status = 'active';
            $user->save();

            return $user;
        });

        Auth::login($user);

        $request->session()->regenerate();

        $this->activityLogger->record(
            action: 'user.registered',
            actor: $user,
            target: $user,
            description: 'Registered a new customer account.',
            metadata: [
                'role' => $user->role,
                'account_status' => $user->account_status,
                'application_flow' => $isApplicationFlow,
            ],
            request: $request
        );

        if ($isApplicationFlow) {
            $request->session()->put('service_application.applicant', [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
            ]);

            return redirect()
                ->route('customer.application.create')
                ->with(
                    'success',
                    'Account created successfully. Complete your Rincomm service application.'
                );
        }

        return redirect()
            ->route('customer.dashboard')
            ->with(
                'success',
                'Account created successfully. Welcome to Rincomm.'
            );
    }
}
