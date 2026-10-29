<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminEmailChangeVerificationMail;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminEmailChangeController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    // Retain old route as a safe compatibility redirect. The only editable UI is shared My Profile.
    public function edit(Request $request): RedirectResponse
    {
        return redirect()->route('profile.edit');
    }

    public function requestChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'new_email' => ['bail', 'required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'current_password' => ['bail', 'required', 'current_password'],
        ]);

        $newEmail = strtolower(trim($validated['new_email']));
        $currentEmail = strtolower(trim($request->user()->email));

        if ($newEmail === $currentEmail) {
            return back()->withErrors(['new_email' => 'Enter an email address different from your current one.']);
        }

        $token = bin2hex(random_bytes(32));
        $change = DB::transaction(function () use ($request, $newEmail, $token) {
            $admin = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            if (! $admin->isAdmin() || ! $admin->isActive()) {
                abort(403);
            }

            if (User::query()->where('email', $newEmail)->exists()) {
                return null;
            }

            DB::table('admin_email_change_requests')->where('user_id', $admin->id)->delete();

            $id = DB::table('admin_email_change_requests')->insertGetId([
                'user_id' => $admin->id,
                'original_email' => $admin->email,
                'requested_email' => $newEmail,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (int) $id;
        });

        if ($change === null) {
            return back()->withErrors(['new_email' => 'This email address is already used by another account.']);
        }

        try {
            Mail::to($newEmail)->send(new AdminEmailChangeVerificationMail(
                route('admin.email-change.confirm', ['change' => $change, 'token' => $token])
            ));
        } catch (Throwable $exception) {
            DB::table('admin_email_change_requests')
                ->where('id', $change)
                ->where('user_id', $request->user()->id)
                ->delete();
            report($exception);

            return back()->withErrors([
                'new_email' => 'Unable to send verification email. Your current login email is unchanged. Please check SMTP settings and try again.',
            ]);
        }

        $this->activityLogger->record(
            action: 'user.admin_email_change_requested',
            actor: $request->user(),
            target: $request->user(),
            description: 'Administrator requested a login email change; verification pending.',
            metadata: ['new_email' => $newEmail],
            request: $request
        );

        return redirect()->route('profile.edit')->with(
            'success',
            'Verification instructions were sent to the new address. Your login email will not change until verified.'
        );
    }

    private function validChange(Request $request, string $change, string $token): ?object
    {
        if (! ctype_digit($change) || ! ctype_xdigit($token) || strlen($token) !== 64) {
            return null;
        }

        $pending = DB::table('admin_email_change_requests')
            ->where('id', $change)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $pending || now()->greaterThan($pending->expires_at)) {
            return null;
        }

        if (! hash_equals($pending->token_hash, hash('sha256', $token))) {
            return null;
        }

        if (strcasecmp($request->user()->email, $pending->original_email) !== 0) {
            return null;
        }

        return $pending;
    }

    public function confirm(Request $request, string $change, string $token): View
    {
        $pending = $this->validChange($request, $change, $token);
        abort_unless($pending, 404);

        return view('admin.account.confirm-email-change', [
            'pending' => $pending,
            'change' => $change,
            'token' => $token,
        ]);
    }

    public function apply(Request $request, string $change, string $token): RedirectResponse
    {
        // Password must be entered again: a forwarded link alone cannot update a login.
        $request->validate(['current_password' => ['required', 'current_password']]);
        abort_unless($this->validChange($request, $change, $token), 404);

        $result = DB::transaction(function () use ($request, $change, $token) {
            $admin = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $pending = DB::table('admin_email_change_requests')
                ->where('id', $change)
                ->where('user_id', $admin->id)
                ->lockForUpdate()
                ->first();

            if (! $pending || now()->greaterThan($pending->expires_at)
                || ! hash_equals($pending->token_hash, hash('sha256', $token))
                || strcasecmp($admin->email, $pending->original_email) !== 0) {
                abort(404);
            }

            if (! $admin->isAdmin() || ! $admin->isActive()) {
                abort(403);
            }

            if (User::query()->where('email', $pending->requested_email)->whereKeyNot($admin->id)->exists()) {
                return null;
            }

            $oldEmail = $admin->email;
            $admin->email = $pending->requested_email;
            $admin->email_verified_at = now(); // The proposed mailbox was verified by the one-use email link.
            $admin->setRememberToken(\Illuminate\Support\Str::random(60));
            $admin->save();

            DB::table('admin_email_change_requests')->where('id', $change)->delete();

            return [$admin, $oldEmail];
        });

        if ($result === null) {
            return back()->withErrors(['current_password' => 'The requested address is no longer available.']);
        }

        [$admin, $oldEmail] = $result;

        $this->activityLogger->record(
            action: 'user.admin_email_changed',
            actor: $admin,
            target: $admin,
            description: 'Administrator verified and changed their login email.',
            metadata: ['old_email' => $oldEmail, 'new_email' => $admin->email],
            request: $request
        );

        // Invalidate database-backed sessions belonging to this administrator (if enabled).
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $admin->id)
                ->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Administrator email verified and updated. Sign in using your new email address.');
    }
}
