<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffProfileRequestsNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'account_status' => 'active'])->save();
        return $user;
    }

    public function test_admin_can_click_requests_from_subscribers_navigation(): void
    {
        $route = route('admin.profile-update-requests.index');
        $this->actingAs($this->staffUser('admin'))
            ->get(route('admin.subscribers.index'))
            ->assertOk()
            ->assertSee('Requests')
            ->assertSee($route, false);
    }

    public function test_staff_review_queue_uses_shared_admin_interface(): void
    {
        $route = route('admin.profile-update-requests.index');
        $this->actingAs($this->staffUser('staff'))
            ->get($route)
            ->assertOk()
            ->assertSee('Subscriber profile update requests')
            ->assertSee('Customers')
            ->assertSee($route, false);
    }
}