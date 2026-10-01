<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\PhilippinePhoneNumberService;
use App\Services\VerificationChallengeService;
use App\Services\VerificationCodeDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly PhilippinePhoneNumberService $phoneNumberService,
        private readonly VerificationChallengeService $challengeService,
        private readonly VerificationCodeDeliveryService $deliveryService
    ) {}

    /**
     * Display the customer registration page.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new customer/applicant account and begin OTP verification.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $email = Str::lower(
            trim(
                (string) $request->input(
                    'email',
                    ''
                )
            )
        );

        try {
            $phone = $this->phoneNumberService->normalize(
                (string) $request->input(
                    'phone',
                    ''
                )
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'phone' => $exception->getMessage(),
            ]);
        }

        $request->merge([
            'email' => $email,
            'phone' => $phone,
        ]);

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
                'phone' => [
                    'bail',
                    'required',
                    'string',
                    'max:20',
                    'unique:users,phone',
                ],
                'verification_channel' => [
                    'bail',
                    'required',
                    'in:email,sms',
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
                'first_name.required' =>
                'Please enter your first name.',
                'last_name.required' =>
                'Please enter your last name.',

                'email.required' =>
                'Please enter your email address.',
                'email.email' =>
                'Please enter a valid email address.',
                'email.unique' =>
                'An account with this email address already exists.',

                'phone.required' =>
                'Please enter your mobile number.',
                'phone.unique' =>
                'An account with this mobile number already exists.',

                'verification_channel.required' =>
                'Please choose how you want to receive your verification code.',
                'verification_channel.in' =>
                'Please choose either Email or SMS verification.',

                'password.required' =>
                'Password must not be empty.',
                'password.min' =>
                'Password must be at least 8 characters.',
                'password_confirmation.required' =>
                'Please confirm your password.',
                'password_confirmation.same' =>
                'Password confirmation does not match.',
            ]
        );

        $isApplicationFlow =
            ($validated['application_flow'] ?? null) === '1'
            && $request->session()->has(
                'service_application.coverage'
            )
            && $request->session()->has(
                'service_application.plan_id'
            );

        $user = null;

        try {
            $user = DB::transaction(
                function () use ($validated): User {
                    $user = User::create([
                        'name' => trim(
                            $validated['first_name']
                                . ' '
                                . $validated['last_name']
                        ),
                        'email' => $validated['email'],
                        'phone' => $validated['phone'],
                        'password' => $validated['password'],
                    ]);

                    /*
                     * Public registration can only create customer accounts.
                     *
                     * The account remains inaccessible until its registration
                     * verification challenge is completed successfully.
                     */
                    $user->forceFill([
                        'role' => 'customer',
                        'account_status' =>
                        'pending_verification',
                        'email_verified_at' => null,
                        'phone_verified_at' => null,
                        'activation_completed_at' => null,
                    ])->save();

                    return $user->fresh();
                }
            );

            $destination =
                $validated['verification_channel']
                === VerificationChallenge::CHANNEL_EMAIL
                ? $validated['email']
                : $validated['phone'];

            $issued = $this->challengeService->issue(
                user: $user,
                purpose: VerificationChallenge::PURPOSE_REGISTRATION,
                channel: $validated['verification_channel'],
                destination: $destination
            );

            /*
             * External email/SMS delivery deliberately occurs outside the
             * database transaction.
             */
            $this->deliveryService->deliver(
                challenge: $issued['challenge'],
                code: $issued['code']
            );
        } catch (Throwable $exception) {
            report($exception);

            if ($user instanceof User) {
                $this->abandonPendingRegistration(
                    $user
                );
            }

            $request->session()->forget([
                'registration.context',
                'registration.verification.challenge',
            ]);

            return back()
                ->withInput(
                    $request->except([
                        'password',
                        'password_confirmation',
                    ])
                )
                ->with(
                    'error',
                    'We could not send your verification code. Please try registering again.'
                );
        }

        $request->session()->regenerate();

        $request->session()->put(
            'registration.context',
            [
                'first_name' =>
                $validated['first_name'],
                'last_name' =>
                $validated['last_name'],
                'application_flow' =>
                $isApplicationFlow,
            ]
        );

        $request->session()->put(
            'registration.verification.challenge',
            $issued['challenge']->public_id
        );

        return redirect()
            ->route(
                'register.verify',
                $issued['challenge']
            )
            ->with(
                'success',
                sprintf(
                    'We sent a six-digit verification code by %s.',
                    $validated['verification_channel']
                        === VerificationChallenge::CHANNEL_EMAIL
                        ? 'email'
                        : 'SMS'
                )
            );
    }

    /**
     * Remove a brand-new registration that failed before a usable OTP could
     * be delivered.
     */
    private function abandonPendingRegistration(
        User $user
    ): void {
        try {
            DB::transaction(
                function () use ($user): void {
                    $lockedUser = User::query()
                        ->whereKey(
                            $user->getKey()
                        )
                        ->lockForUpdate()
                        ->first();

                    if (! $lockedUser instanceof User) {
                        return;
                    }

                    if (
                        $lockedUser->role !== 'customer'
                        || $lockedUser->account_status
                        !== 'pending_verification'
                        || $lockedUser->activation_completed_at !== null
                        || $lockedUser->customer()->exists()
                        || $lockedUser->technician()->exists()
                        || $lockedUser->serviceApplications()->exists()
                    ) {
                        throw new RuntimeException(
                            'Pending registration cleanup was blocked because related account data exists.'
                        );
                    }

                    $lockedUser->delete();
                }
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
