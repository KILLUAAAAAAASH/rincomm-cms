<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private JobOrder $jobOrder;
    private User $admin;
    private User $technician;
    private ServiceRequest $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->admin = User::factory()->create([
            'role' => 'admin', 'account_status' => 'active',
        ]);
        $technician = Technician::query()->whereHas('user')->firstOrFail();
        $this->technician = $technician->user;
        $this->technician->forceFill([
            'role' => 'technician', 'account_status' => 'active',
        ])->save();
        $this->ticket = ServiceRequest::query()->firstOrFail();
        $this->ticket->forceFill(['status' => 'open'])->save();
        $this->jobOrder = JobOrder::query()->firstOrFail();
        $this->jobOrder->forceFill([
            'status' => 'assigned',
            'technician_id' => $technician->id,
            'service_request_id' => $this->ticket->id,
            'job_type' => 'repair',
            'scheduled_date' => now('Asia/Manila')->subDay()->toDateString(),
            'scheduled_time' => '09:00:00',
            'started_at' => null,
            'completed_at' => null,
        ])->save();
    }

    private function cancel(array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->patch(route('admin.job-orders.cancel', $this->jobOrder),
            $data + ['cancellation_reason' => 'Customer case closed; no visit is necessary.']);
    }

    public function test_admin_cancels_assigned_job_with_reason_and_atomic_audit(): void
    {
        $this->actingAs($this->admin);
        $this->cancel()->assertRedirect(route('admin.job-orders.index'))
            ->assertSessionHas('success');

        $this->jobOrder->refresh();
        $this->assertSame('cancelled', $this->jobOrder->status);
        $this->assertSame($this->admin->id, $this->jobOrder->cancelled_by_user_id);
        $this->assertNotNull($this->jobOrder->cancelled_at);
        $this->assertSame('Customer case closed; no visit is necessary.', $this->jobOrder->cancellation_reason);
        $this->assertNull($this->jobOrder->completed_at);
        $this->assertNull($this->jobOrder->started_at);
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $this->admin->id,
            'action' => 'job_order.cancelled',
        ]);
        $audit = ActivityLog::query()->where('action', 'job_order.cancelled')->latest('id')->firstOrFail();
        $this->assertSame($this->jobOrder->id, $audit->metadata['job_order_id']);
        $this->assertSame('assigned', $audit->metadata['previous_status']);
    }

    public function test_staff_can_cancel_pending_order_without_assigning_technician(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'account_status' => 'active']);
        $this->jobOrder->forceFill([
            'status' => 'pending', 'technician_id' => null,
            'scheduled_date' => null, 'scheduled_time' => null,
        ])->save();
        $this->actingAs($staff)->cancel()->assertSessionHas('success');
        $this->assertSame('cancelled', $this->jobOrder->fresh()->status);
    }

    public function test_cancellation_requires_meaningful_reason(): void
    {
        $this->actingAs($this->admin)
            ->cancel(['cancellation_reason' => ' '])
            ->assertSessionHasErrors('cancellation_reason');
        $this->cancel(['cancellation_reason' => 'short'])
            ->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('assigned', $this->jobOrder->fresh()->status);
        $this->assertSame(0, ActivityLog::query()->where('action', 'job_order.cancelled')->count());
    }

    public function test_customer_and_technician_cannot_cancel_order(): void
    {
        foreach (['customer', 'technician'] as $role) {
            $user = User::factory()->create(['role' => $role, 'account_status' => 'active']);
            $this->actingAs($user)->cancel()->assertForbidden();
        }
        $this->assertSame('assigned', $this->jobOrder->fresh()->status);
    }

    public function test_started_completed_and_already_cancelled_orders_cannot_be_cancelled(): void
    {
        $this->actingAs($this->admin);
        foreach (['in_progress', 'completed', 'cancelled'] as $status) {
            $this->jobOrder->forceFill(['status' => $status])->save();
            $this->cancel()->assertSessionHasErrors('cancellation_reason');
            $this->assertSame($status, $this->jobOrder->fresh()->status);
        }
        $this->jobOrder->forceFill(['status' => 'assigned', 'started_at' => now()])->save();
        $this->cancel()->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('assigned', $this->jobOrder->fresh()->status);
    }

    public function test_cancelled_order_cannot_be_started_by_technician(): void
    {
        $this->actingAs($this->admin)->cancel()->assertSessionHas('success');
        $this->actingAs($this->technician)
            ->patch(route('technician.job-orders.start', $this->jobOrder))
            ->assertSessionHasErrors('job_order');
        $this->assertSame('cancelled', $this->jobOrder->fresh()->status);
    }

    public function test_closed_linked_ticket_blocks_start_even_when_order_assigned(): void
    {
        $this->ticket->forceFill(['status' => 'closed'])->save();
        $this->actingAs($this->technician)
            ->patch(route('technician.job-orders.start', $this->jobOrder))
            ->assertSessionHasErrors('job_order');
        $this->assertSame('assigned', $this->jobOrder->fresh()->status);
        $this->assertNull($this->jobOrder->fresh()->started_at);
    }

    public function test_admin_index_exposes_cancel_action_only_for_eligible_orders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.job-orders.index'))
            ->assertOk()
            ->assertSee('Cancel Job Order')
            ->assertSee(route('admin.job-orders.cancel', $this->jobOrder));
    }
}
