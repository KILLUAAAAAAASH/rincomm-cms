<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerProfileUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffProfileUpdateReviewTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'account_status' => 'active'])->save();
        return $user;
    }

    private function pending(): array
    {
        $owner = $this->user('customer');
        $customer = Customer::create([
            'user_id' => $owner->id,
            'customer_code' => 'F5-REVIEW-'.uniqid(),
            'first_name' => 'Original',
            'last_name' => 'Customer',
            'address' => 'Original Address',
            'phone' => '09120000000',
            'status' => 'active',
        ]);
        $item = CustomerProfileUpdateRequest::create([
            'customer_id' => $customer->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
            'current_values' => [
                'phone' => '09120000000', 'address' => 'Original Address',
                'city' => null, 'province' => null, 'postal_code' => null, 'billing_address' => null,
            ],
            'requested_values' => [
                'phone' => '09990000000', 'address' => 'Original Address',
                'city' => null, 'province' => null, 'postal_code' => null, 'billing_address' => null,
            ],
            'reason' => 'Correct my contact number',
        ]);
        return [$owner, $customer, $item];
    }

    public function test_staff_can_review_pending_requests(): void
    {
        [, , $item] = $this->pending();
        $this->actingAs($this->user('staff'))
            ->get(route('admin.profile-update-requests.index'))
            ->assertOk()->assertSee('Request #'.$item->id)->assertSee('09990000000');
    }

    public function test_approval_applies_only_requested_changes_and_records_reviewer(): void
    {
        [, $customer, $item] = $this->pending();
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.profile-update-requests.approve', $item), [
            'review_notes' => 'Verified subscriber request.',
        ])->assertRedirect(route('admin.profile-update-requests.index'));
        $this->assertSame('09990000000', $customer->fresh()->phone);
        $this->assertSame('Original Address', $customer->fresh()->address);
        $this->assertSame('approved', $item->fresh()->status);
        $this->assertSame($admin->id, $item->fresh()->reviewed_by);
        $this->assertNotNull($item->fresh()->applied_at);
    }

    public function test_rejection_preserves_profile_and_saves_reason(): void
    {
        [, $customer, $item] = $this->pending();
        $staff = $this->user('staff');
        $this->actingAs($staff)->post(route('admin.profile-update-requests.reject', $item), [
            'review_notes' => 'Contact number not verified.',
        ])->assertRedirect();
        $this->assertSame('09120000000', $customer->fresh()->phone);
        $this->assertSame('rejected', $item->fresh()->status);
        $this->assertSame($staff->id, $item->fresh()->reviewed_by);
        $this->assertNull($item->fresh()->applied_at);
    }

    public function test_cannot_approve_twice_or_reject_after_approval(): void
    {
        [, $customer, $item] = $this->pending();
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.profile-update-requests.approve', $item), [
            'review_notes' => 'Verified subscriber request.',
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.profile-update-requests.reject', $item), [
            'review_notes' => 'Not allowed after approval.',
        ])->assertSessionHasErrors('request');
        $this->assertSame('approved', $item->fresh()->status);
        $this->assertSame('09990000000', $customer->fresh()->phone);
    }

    public function test_stale_request_cannot_overwrite_staff_edit(): void
    {
        [, $customer, $item] = $this->pending();
        $customer->phone = '09181111111';
        $customer->save();
        $this->actingAs($this->user('staff'))->post(route('admin.profile-update-requests.approve', $item), [
            'review_notes' => 'Verified subscriber request.',
        ])->assertSessionHasErrors('request');
        $this->assertSame('09181111111', $customer->fresh()->phone);
        $this->assertSame('pending', $item->fresh()->status);
    }

    public function test_customer_cannot_access_staff_queue_or_make_decisions(): void
    {
        [$owner, , $item] = $this->pending();
        $this->actingAs($owner)->get(route('admin.profile-update-requests.index'))->assertForbidden();
        $this->actingAs($owner)->post(route('admin.profile-update-requests.approve', $item), [
            'review_notes' => 'Cannot approve this request.',
        ])->assertForbidden();
        $this->assertSame('pending', $item->fresh()->status);
    }

    public function test_short_review_reason_is_rejected(): void
    {
        [, $customer, $item] = $this->pending();
        $this->actingAs($this->user('staff'))->post(route('admin.profile-update-requests.approve', $item), [
            'review_notes' => 'short',
        ])->assertSessionHasErrors('review_notes');
        $this->assertSame('09120000000', $customer->fresh()->phone);
    }
}
