<?php

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_middleware_allows_an_authorized_role(): void
    {
        $user = $this->createUser('admin');

        $request = Request::create('/protected', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = (new RoleMiddleware())->handle(
            $request,
            fn () => new Response('allowed', 200),
            'admin',
            'staff'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    public function test_role_middleware_rejects_an_unauthorized_role(): void
    {
        $user = $this->createUser('customer');

        $request = Request::create('/protected', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionCode(0);

        (new RoleMiddleware())->handle(
            $request,
            fn () => new Response('allowed', 200),
            'admin',
            'staff'
        );
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $user = $this->createUser('customer');

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_customer_cannot_access_admin_user_management(): void
    {
        $user = $this->createUser('customer');

        $this
            ->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_technician_cannot_access_admin_dashboard(): void
    {
        $user = $this->createUser('technician');

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_customer_dashboard(): void
    {
        $user = $this->createUser('admin');

        $this
            ->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_technician_dashboard(): void
    {
        $user = $this->createUser('admin');

        $this
            ->actingAs($user)
            ->get(route('technician.dashboard'))
            ->assertForbidden();
    }

    public function test_customer_cannot_access_technician_dashboard(): void
    {
        $user = $this->createUser('customer');

        $this
            ->actingAs($user)
            ->get(route('technician.dashboard'))
            ->assertForbidden();
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }
}
