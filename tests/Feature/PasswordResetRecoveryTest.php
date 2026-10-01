<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(
            route('password.email'),
            [
                'email' => $user->email,
            ]
        );

        $response->assertSessionHas('status');

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );

        $this->assertDatabaseHas(
            'password_reset_tokens',
            [
                'email' => $user->email,
            ]
        );
    }

    public function test_forgot_password_requires_valid_email_address(): void
    {
        Notification::fake();

        $response = $this->post(
            route('password.email'),
            [
                'email' => 'not-an-email',
            ]
        );

        $response->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_valid_reset_token_changes_password_and_consumes_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post(
            route('password.update'),
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ]
        );

        $response->assertRedirect(
            route('login')
        );

        $response->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'NewSecurePassword123!',
                $user->password
            )
        );

        $this->assertDatabaseMissing(
            'password_reset_tokens',
            [
                'email' => $user->email,
            ]
        );
    }

    public function test_invalid_reset_token_does_not_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->post(
            route('password.update'),
            [
                'token' => 'invalid-reset-token',
                'email' => $user->email,
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ]
        );

        $response->assertSessionHasErrors('email');

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'old-password',
                $user->password
            )
        );
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post(
            route('password.update'),
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]
        );

        $response->assertSessionHasErrors('password');
    }
}
