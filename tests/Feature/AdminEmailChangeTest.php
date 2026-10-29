<?php

namespace Tests\Feature;

use App\Mail\AdminEmailChangeVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'admin', string $email = 'original-admin@example.com'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => 'ConfirmedAdminPassword2026!',
        ]);
        $user->forceFill(['role' => $role, 'account_status' => 'active'])->save();
        return $user;
    }

    private function requestChange(User $admin, string $new = 'new-admin@example.com'): string
    {
        Mail::fake();
        $this->actingAs($admin)->post(route('admin.email-change.request'), [
            'new_email' => $new,
            'current_password' => 'ConfirmedAdminPassword2026!',
        ])->assertRedirect(route('profile.edit'));

        $link = null;
        Mail::assertSent(AdminEmailChangeVerificationMail::class, function ($mail) use (&$link, $new) {
            if (! $mail->hasTo($new)) { return false; }
            $link = $mail->confirmationUrl;
            return true;
        });
        $this->assertNotNull($link);
        return $link;
    }

    public function test_admin_navigation_and_form_are_accessible_from_users_menu(): void
    {
        $admin = $this->user();
        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()->assertSee('My Profile')
            ->assertSee(route('profile.edit'), false);
        $this->actingAs($admin)->get(route('profile.edit'))
            ->assertOk()->assertSee('Send Email Verification')
            ->assertSee('form="admin-email-change-form"', false)
            ->assertSee('name="new_email"', false);
        $this->actingAs($admin)->get(route('admin.email-change.edit'))
            ->assertRedirect(route('profile.edit'));
    }

    public function test_staff_customer_and_guest_cannot_use_admin_email_settings(): void
    {
        $this->get(route('admin.email-change.edit'))->assertRedirect();
        foreach (['staff', 'customer'] as $role) {
            $user = $this->user($role, $role.'-user@example.com');
            $this->actingAs($user)->get(route('admin.email-change.edit'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.email-change.request'), [
                'new_email' => 'unused@example.com',
                'current_password' => 'ConfirmedAdminPassword2026!',
            ])->assertForbidden();
        }
    }

    public function test_wrong_password_and_duplicate_address_are_rejected(): void
    {
        $admin = $this->user();
        $other = $this->user('admin', 'occupied@example.com');
        $this->actingAs($admin)->post(route('admin.email-change.request'), [
            'new_email' => 'another@example.com', 'current_password' => 'wrong',
        ])->assertSessionHasErrors('current_password');
        $this->actingAs($admin)->post(route('admin.email-change.request'), [
            'new_email' => $other->email, 'current_password' => 'ConfirmedAdminPassword2026!',
        ])->assertSessionHasErrors('new_email');
        $this->assertDatabaseCount('admin_email_change_requests', 0);
    }

    public function test_request_does_not_change_login_email_until_confirmed(): void
    {
        $admin = $this->user();
        $link = $this->requestChange($admin);
        $this->assertNotEmpty($link);
        $this->assertSame('original-admin@example.com', $admin->fresh()->email);
        $this->assertDatabaseHas('admin_email_change_requests', ['user_id' => $admin->id, 'requested_email' => 'new-admin@example.com']);
    }

    public function test_confirm_requires_password_and_then_updates_email_once(): void
    {
        $admin = $this->user();
        $link = $this->requestChange($admin);
        $this->actingAs($admin)->get($link)->assertOk()->assertSee('Confirm your new administrator email');
        $this->actingAs($admin)->post($link, ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertSame('original-admin@example.com', $admin->fresh()->email);
        $this->actingAs($admin)->post($link, ['current_password' => 'ConfirmedAdminPassword2026!'])->assertRedirect(route('login'));
        $this->assertSame('new-admin@example.com', $admin->fresh()->email);
        $this->assertNotNull($admin->fresh()->email_verified_at);
        $this->assertDatabaseCount('admin_email_change_requests', 0);
        $this->get($link)->assertRedirect();
    }

    public function test_expired_or_tampered_links_cannot_change_email(): void
    {
        $admin = $this->user();
        $link = $this->requestChange($admin);
        $changed = substr($link, 0, -1) . (substr($link, -1) === 'a' ? 'b' : 'a');
        $this->actingAs($admin)->get($changed)->assertNotFound();
        DB::table('admin_email_change_requests')->where('user_id', $admin->id)->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($admin)->post($link, ['current_password' => 'ConfirmedAdminPassword2026!'])->assertNotFound();
        $this->assertSame('original-admin@example.com', $admin->fresh()->email);
    }

    public function test_inactive_admin_cannot_submit_email_change(): void
    {
        $admin = $this->user();
        $admin->forceFill(['account_status' => 'inactive'])->save();
        $this->actingAs($admin)->post(route('admin.email-change.request'), [
            'new_email' => 'other@example.com', 'current_password' => 'ConfirmedAdminPassword2026!',
        ])->assertStatus(403);
    }
    public function test_customer_profile_does_not_expose_admin_email_editor(): void
    {
        $customer = $this->user('customer', 'customer-test@example.com');
        $this->actingAs($customer)->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('admin-email-change-form')
            ->assertDontSee('Send Email Verification');
    }

}
