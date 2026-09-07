<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
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
            ],
            [
                'account_status.required' => 'Please select an account status.',
                'account_status.in' => 'The selected account status is invalid.',
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

        $user->account_status = $validated['account_status'];
        $user->save();

        $message = $user->account_status === 'active'
            ? 'User account activated successfully.'
            : 'User account deactivated successfully.';

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }
}
