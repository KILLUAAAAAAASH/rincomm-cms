<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PhilippinePhoneNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class UserController extends Controller
{
    private const DEFAULT_EMPLOYEE_PASSWORD = '12345678';

    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly PhilippinePhoneNumberService $phoneNumberService
    ) {}

    /**
     * Display the user account list.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->query(
                'search',
                ''
            )
        );

        $role = (string) $request->query(
            'role',
            ''
        );

        $status = (string) $request->query(
            'status',
            ''
        );

        $allowedRoles = [
            'admin',
            'staff',
            'technician',
            'customer',
        ];

        $allowedStatuses = [
            'active',
            'inactive',
            'pending_verification',
        ];

        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'employee_number',
                'role',
                'account_status',
                'created_at',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(
                        function ($query) use ($search) {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'employee_number',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                in_array(
                    $role,
                    $allowedRoles,
                    true
                ),
                fn ($query) =>
                $query->where(
                    'role',
                    $role
                )
            )
            ->when(
                in_array(
                    $status,
                    $allowedStatuses,
                    true
                ),
                fn ($query) =>
                $query->where(
                    'account_status',
                    $status
                )
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $activeAdministratorCount =
            User::query()
                ->where(
                    'role',
                    'admin'
                )
                ->where(
                    'account_status',
                    'active'
                )
                ->count();

        return view(
            'admin.users.index',
            [
                'users' => $users,
                'activeAdministratorCount' =>
                $activeAdministratorCount,
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ]
        );
    }

    /**
     * Display the employee account creation page.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Create a new Staff or Technician employee account.
     *
     * New employee accounts remain pending until the employee completes the
     * first-time SMS verification and password-change workflow.
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
            $phone =
                $this->phoneNumberService->normalize(
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
                'name' => [
                    'bail',
                    'required',
                    'string',
                    'max:255',
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
                'role' => [
                    'bail',
                    'required',
                    'in:staff,technician',
                ],
                'specialization' => [
                    'exclude_unless:role,technician',
                    'nullable',
                    'string',
                    'max:120',
                ],
            ],
            [
                'name.required' =>
                'Please enter the employee name.',
                'name.max' =>
                'The employee name must not exceed 255 characters.',

                'email.required' =>
                'Please enter the employee email address.',
                'email.email' =>
                'Please enter a valid email address.',
                'email.unique' =>
                'An account with this email address already exists.',

                'phone.required' =>
                'Please enter the employee mobile number.',
                'phone.unique' =>
                'An account with this mobile number already exists.',

                'role.required' =>
                'Please select an employee role.',
                'role.in' =>
                'Only Staff or Technician accounts can be created here.',

                'specialization.max' =>
                'The specialization must not exceed 120 characters.',
            ]
        );

        $created = DB::transaction(
            function () use (
                $validated
            ): array {
                $user = User::create([
                    'name' => trim(
                        $validated['name']
                    ),
                    'email' =>
                        $validated['email'],
                    'phone' =>
                        $validated['phone'],
                    'password' =>
                        self::DEFAULT_EMPLOYEE_PASSWORD,
                ]);

                $employeeNumber =
                    $this->employeeNumberFor(
                        $user
                    );

                $user->forceFill([
                    'employee_number' =>
                        $employeeNumber,
                    'role' =>
                        $validated['role'],
                    'account_status' =>
                        'pending_verification',
                    'email_verified_at' =>
                        null,
                    'phone_verified_at' =>
                        null,
                    'activation_completed_at' =>
                        null,
                ])->save();

                $technicianCode = null;

                if (
                    $validated['role']
                    === 'technician'
                ) {
                    $technicianCode =
                        $this->technicianCodeFor(
                            $user
                        );

                    Technician::create([
                        'user_id' =>
                            $user->id,
                        'technician_code' =>
                            $technicianCode,
                        'specialization' =>
                            isset(
                                $validated[
                                    'specialization'
                                ]
                            )
                                ? trim(
                                    $validated[
                                        'specialization'
                                    ]
                                )
                                : null,
                        'status' =>
                            'available',
                    ]);
                }

                return [
                    'user' =>
                        $user->fresh(),
                    'employee_number' =>
                        $employeeNumber,
                    'technician_code' =>
                        $technicianCode,
                ];
            }
        );

        $user = $created['user'];

        $this->activityLogger->record(
            action: 'user.employee_created',
            actor: $request->user(),
            target: $user,
            description:
                "Created the {$user->role} employee account for {$user->name}.",
            metadata: [
                'role' =>
                    $user->role,
                'employee_number' =>
                    $created[
                        'employee_number'
                    ],
                'technician_code' =>
                    $created[
                        'technician_code'
                    ],
                'account_status' =>
                    $user->account_status,
            ],
            request: $request
        );

        return redirect()
            ->route(
                'admin.users.create'
            )
            ->with(
                'created_employee',
                [
                    'name' =>
                        $user->name,
                    'role' =>
                        $user->role,
                    'employee_number' =>
                        $created[
                            'employee_number'
                        ],
                    'technician_code' =>
                        $created[
                            'technician_code'
                        ],
                    'default_password' =>
                        self::DEFAULT_EMPLOYEE_PASSWORD,
                ]
            )
            ->with(
                'success',
                'Employee account created successfully.'
            );
    }

    /**
     * Activate or deactivate a user account.
     *
     * Verification-pending accounts are intentionally excluded from this
     * administrative status endpoint. Their activation must only occur
     * through the appropriate verification workflow.
     */
    public function updateStatus(
        Request $request,
        User $user
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'account_status' => [
                    'required',
                    'in:active,inactive',
                ],
                'deactivation_reason' => [
                    'nullable',
                    'required_if:account_status,inactive',
                    'string',
                    'max:500',
                ],
            ],
            [
                'account_status.required' =>
                'Please select an account status.',
                'account_status.in' =>
                'The selected account status is invalid.',

                'deactivation_reason.required_if' =>
                'Please provide a reason for deactivating this account.',
                'deactivation_reason.string' =>
                'The deactivation reason must be valid text.',
                'deactivation_reason.max' =>
                'The deactivation reason must not exceed 500 characters.',
            ]
        );

        if (
            ! in_array(
                $user->account_status,
                [
                    'active',
                    'inactive',
                ],
                true
            )
        ) {
            return redirect()
                ->route(
                    'admin.users.index'
                )
                ->with(
                    'error',
                    'This account status is controlled by its verification workflow and cannot be changed manually.'
                );
        }

        if (
            $request->user()->is($user)
            && $validated[
                'account_status'
            ] === 'inactive'
        ) {
            return redirect()
                ->route(
                    'admin.users.index'
                )
                ->with(
                    'error',
                    'You cannot deactivate your own account.'
                );
        }

        if (
            $user->role === 'admin'
            && $user->account_status === 'active'
            && $validated[
                'account_status'
            ] === 'inactive'
        ) {
            $activeAdministratorCount =
                User::query()
                    ->where(
                        'role',
                        'admin'
                    )
                    ->where(
                        'account_status',
                        'active'
                    )
                    ->count();

            if (
                $activeAdministratorCount
                <= 1
            ) {
                return redirect()
                    ->route(
                        'admin.users.index'
                    )
                    ->with(
                        'error',
                        'The last active administrator cannot be deactivated.'
                    );
            }
        }

        $previousStatus =
            $user->account_status;

        $deactivationReason =
            $validated[
                'account_status'
            ] === 'inactive'
                ? trim(
                    $validated[
                        'deactivation_reason'
                    ]
                )
                : null;

        $user->account_status =
            $validated[
                'account_status'
            ];

        $user->save();

        if (
            $previousStatus
            !== $user->account_status
        ) {
            $metadata = [
                'old_status' =>
                    $previousStatus,
                'new_status' =>
                    $user->account_status,
            ];

            if (
                $deactivationReason
                !== null
            ) {
                $metadata['reason'] =
                    $deactivationReason;
            }

            if (
                $user->account_status
                === 'active'
            ) {
                $action =
                    'user.activated';

                $description =
                    "Activated the account of {$user->name}.";
            } else {
                $action =
                    'user.deactivated';

                $description =
                    sprintf(
                        'Deactivated the account of %s. Reason: %s',
                        $user->name,
                        $deactivationReason
                    );
            }

            $this->activityLogger->record(
                action: $action,
                actor: $request->user(),
                target: $user,
                description: $description,
                metadata: $metadata,
                request: $request
            );
        }

        $message =
            $user->account_status
            === 'active'
                ? 'User account activated successfully.'
                : 'User account deactivated successfully.';

        return redirect()
            ->route(
                'admin.users.index'
            )
            ->with(
                'success',
                $message
            );
    }

    /**
     * Generate a stable employee number from the user primary key.
     */
    private function employeeNumberFor(
        User $user
    ): string {
        return sprintf(
            'EMP-%04d',
            $user->id
        );
    }

    /**
     * Generate the Technician operational code independently from the
     * employee authentication identifier.
     */
    private function technicianCodeFor(
        User $user
    ): string {
        return sprintf(
            'TECH-%04d',
            $user->id
        );
    }
}