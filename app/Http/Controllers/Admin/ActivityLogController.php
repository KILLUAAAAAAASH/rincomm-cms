<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display system activity related to user management.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $action = (string) $request->query('action', '');
        $date = trim((string) $request->query('date', ''));

        $sort = (string) $request->query('sort', 'newest');

        if (! in_array($sort, [
            'newest',
            'oldest',
            'action_asc',
            'action_desc',
        ], true)) {
            $sort = 'newest';
        }

        $parsedDate = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if (
            $date !== '' &&
            (
                ! $parsedDate ||
                $parsedDate->format('Y-m-d') !== $date
            )
        ) {
            $date = '';
        }

        $actions = [
            'user.registered' => 'Registered',
            'user.logged_in' => 'Logged In',
            'user.logged_out' => 'Logged Out',
            'user.profile_updated' => 'Profile Updated',
            'user.password_reset' => 'Password Reset',
            'user.activated' => 'Account Activated',
            'user.deactivated' => 'Account Deactivated',
        ];

        $logs = ActivityLog::query()
            ->with([
                'actor:id,name,email,role',
                'targetUser:id,name,email,role',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('action', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('ip_address', 'like', "%{$search}%")
                            ->orWhereHas(
                                'actor',
                                function ($query) use ($search) {
                                    $query
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                }
                            )
                            ->orWhereHas(
                                'targetUser',
                                function ($query) use ($search) {
                                    $query
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                }
                            );
                    });
                }
            )
            ->when(
                array_key_exists($action, $actions),
                fn($query) => $query->where('action', $action)
            )
            ->when(
                $date !== '',
                fn($query) => $query->whereDate(
                    'created_at',
                    $date
                )
            )
            ->when(
                $sort === 'newest',
                fn ($query) => $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
            )
            ->when(
                $sort === 'oldest',
                fn ($query) => $query
                    ->orderBy('created_at')
                    ->orderBy('id')
            )
            ->when(
                $sort === 'action_asc',
                fn ($query) => $query
                    ->orderBy('action')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
            )
            ->when(
                $sort === 'action_desc',
                fn ($query) => $query
                    ->orderByDesc('action')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
            )
            ->paginate(25)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'actions' => $actions,
            'search' => $search,
            'action' => $action,
            'date' => $date,
            'sort' => $sort,
        ]);
    }
}
