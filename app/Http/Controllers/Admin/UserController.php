<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Display the user account list.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');

        $allowedRoles = [
            'admin',
            'staff',
            'technician',
            'customer',
        ];

        $allowedStatuses = [
            'active',
            'inactive',
        ];

        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'account_status',
                'created_at',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                in_array($role, $allowedRoles, true),
                fn($query) => $query->where('role', $role)
            )
            ->when(
                in_array($status, $allowedStatuses, true),
                fn($query) => $query->where('account_status', $status)
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $activeAdministratorCount = User::query()
            ->where('role', 'admin')
            ->where('account_status', 'active')
            ->count();

        return view('admin.users.index', [
            'users' => $users,
            'activeAdministratorCount' => $activeAdministratorCount,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    /**
     * Activate or deactivate a user account.
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
                'account_status.required' => 'Please select an account status.',
                'account_status.in' => 'The selected account status is invalid.',

                'deactivation_reason.required_if' => 'Please provide a reason for deactivating this account.',
                'deactivation_reason.string' => 'The deactivation reason must be valid text.',
                'deactivation_reason.max' => 'The deactivation reason must not exceed 500 characters.',
            ]
        );

        if (
            $request->user()->is($user) &&
            $validated['account_status'] === 'inactive'
        ) {
            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'You cannot deactivate your own account.'
                );
        }

        if (
            $user->role === 'admin' &&
            $user->account_status === 'active' &&
            $validated['account_status'] === 'inactive'
        ) {
            $activeAdministratorCount = User::query()
                ->where('role', 'admin')
                ->where('account_status', 'active')
                ->count();

            if ($activeAdministratorCount <= 1) {
                return redirect()
                    ->route('admin.users.index')
                    ->with(
                        'error',
                        'The last active administrator cannot be deactivated.'
                    );
            }
        }

        $previousStatus = $user->account_status;

        $deactivationReason = $validated['account_status'] === 'inactive'
            ? trim($validated['deactivation_reason'])
            : null;

        $user->account_status = $validated['account_status'];
        $user->save();

        if ($previousStatus !== $user->account_status) {
            $metadata = [
                'old_status' => $previousStatus,
                'new_status' => $user->account_status,
            ];

            if ($deactivationReason !== null) {
                $metadata['reason'] = $deactivationReason;
            }

            if ($user->account_status === 'active') {
                $action = 'user.activated';

                $description = "Activated the account of {$user->name}.";
            } else {
                $action = 'user.deactivated';

                $description = sprintf(
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

        $message = $user->account_status === 'active'
            ? 'User account activated successfully.'
            : 'User account deactivated successfully.';

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }
}
