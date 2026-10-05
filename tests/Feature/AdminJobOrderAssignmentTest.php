<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminJobOrderAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_assign_pending_job_order_and_schedule_it(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser('staff');

        $customer = Customer::query()->firstOrFail();

        $technician = Technician::query()
            ->where('status', 'available')
            ->whereHas(
                'user',
                fn ($query) => $query
                    ->where('role', 'technician')
                    ->where('account_status', 'active')
            )
            ->firstOrFail();

        $jobOrder = $this->createJobOrder(
            customer: $customer,
            status: 'pending'
        );

        $scheduledDate = now()
            ->addDay()
            ->toDateString();

        $response = $this
            ->actingAs($staff)
            ->from(route('admin.job-orders.index'))
            ->patch(
                route(
                    'admin.job-orders.assignment.update',
                    $jobOrder
                ),
                [
                    'assignment_job_order_id' => $jobOrder->id,
                    'technician_id' => $technician->id,
                    'scheduled_date' => $scheduledDate,
                    'scheduled_time' => '09:30',
                ]
            );

        $response
            ->assertRedirect(
                route('admin.job-orders.index')
            )
            ->assertSessionHas(
                'success',
                'Job Order assigned and scheduled successfully.'
            );

        $jobOrder->refresh();

        $this->assertSame(
            'assigned',
            $jobOrder->status
        );

        $this->assertSame(
            $technician->id,
            $jobOrder->technician_id
        );

        $this->assertSame(
            $scheduledDate,
            $jobOrder->scheduled_date?->toDateString()
        );

        $this->assertSame(
            '09:30',
            substr(
                (string) $jobOrder->scheduled_time,
                0,
                5
            )
        );
    }

    public function test_admin_can_reassign_and_reschedule_assigned_job_order(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $customer = Customer::query()->firstOrFail();

        $originalTechnician = Technician::query()
            ->where('status', 'available')
            ->firstOrFail();

        $replacementTechnician =
            $this->createTechnician(
                status: 'available',
                accountStatus: 'active',
                code: 'TECH-TEST-0002'
            );

        $jobOrder = $this->createJobOrder(
            customer: $customer,
            status: 'assigned',
            technician: $originalTechnician,
            scheduledDate: now()
                ->addDay()
                ->toDateString(),
            scheduledTime: '08:00'
        );

        $newScheduledDate = now()
            ->addDays(2)
            ->toDateString();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.job-orders.index'))
            ->patch(
                route(
                    'admin.job-orders.assignment.update',
                    $jobOrder
                ),
                [
                    'assignment_job_order_id' => $jobOrder->id,
                    'technician_id' => $replacementTechnician->id,
                    'scheduled_date' => $newScheduledDate,
                    'scheduled_time' => '13:45',
                ]
            );

        $response
            ->assertRedirect(
                route('admin.job-orders.index')
            )
            ->assertSessionHas(
                'success',
                'Job Order assignment and schedule updated successfully.'
            );

        $jobOrder->refresh();

        $this->assertSame(
            'assigned',
            $jobOrder->status
        );

        $this->assertSame(
            $replacementTechnician->id,
            $jobOrder->technician_id
        );

        $this->assertSame(
            $newScheduledDate,
            $jobOrder->scheduled_date?->toDateString()
        );

        $this->assertSame(
            '13:45',
            substr(
                (string) $jobOrder->scheduled_time,
                0,
                5
            )
        );
    }

    public function test_customer_and_technician_cannot_update_admin_job_order_assignment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customerUser =
            $this->createActiveUser('customer');

        $technicianUser =
            $this->createActiveUser('technician');

        $customer = Customer::query()->firstOrFail();

        $eligibleTechnician = Technician::query()
            ->where('status', 'available')
            ->firstOrFail();

        $jobOrder = $this->createJobOrder(
            customer: $customer,
            status: 'pending'
        );

        $payload = [
            'assignment_job_order_id' => $jobOrder->id,
            'technician_id' => $eligibleTechnician->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '10:00',
        ];

        $this->actingAs($customerUser)
            ->patch(
                route(
                    'admin.job-orders.assignment.update',
                    $jobOrder
                ),
                $payload
            )
            ->assertForbidden();

        $jobOrder->refresh();

        $this->assertSame(
            'pending',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->technician_id
        );

        $this->actingAs($technicianUser)
            ->patch(
                route(
                    'admin.job-orders.assignment.update',
                    $jobOrder
                ),
                $payload
            )
            ->assertForbidden();

        $jobOrder->refresh();

        $this->assertSame(
            'pending',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->technician_id
        );
    }

    public function test_unavailable_technician_cannot_be_assigned(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $customer = Customer::query()->firstOrFail();

        $unavailableTechnician =
            $this->createTechnician(
                status: 'unavailable',
                accountStatus: 'active',
                code: 'TECH-UNAVAILABLE'
            );

        $jobOrder = $this->createJobOrder(
            customer: $customer,
            status: 'pending'
        );

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.job-orders.index'))
            ->patch(
                route(
                    'admin.job-orders.assignment.update',
                    $jobOrder
                ),
                [
                    'assignment_job_order_id' => $jobOrder->id,
                    'technician_id' => $unavailableTechnician->id,
                    'scheduled_date' => now()->addDay()->toDateString(),
                    'scheduled_time' => '09:00',
                ]
            );

        $response
            ->assertRedirect(
                route('admin.job-orders.index')
            )
            ->assertSessionHasErrors(
                'technician_id'
            );

        $jobOrder->refresh();

        $this->assertSame(
            'pending',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->technician_id
        );

        $this->assertNull(
            $jobOrder->scheduled_date
        );

        $this->assertNull(
            $jobOrder->scheduled_time
        );
    }

    public function test_technician_with_inactive_account_cannot_be_assigned(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $customer = Customer::query()->firstOrFail();

        $inactiveTechnician =
            $this->createTechnician(
                status: 'available',
                accountStatus: 'inactive',
                code: 'TECH-INACTIVE'
            );

        $jobOrder = $this->createJobOrder(
            customer: $customer,
            status: 'pending'
        );

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.job-orders.index'))
            ->patch(
                route(
                    'admin.job-orders.assignment.update',
                    $jobOrder
                ),
                [
                    'assignment_job_order_id' => $jobOrder->id,
                    'technician_id' => $inactiveTechnician->id,
                    'scheduled_date' => now()->addDay()->toDateString(),
                    'scheduled_time' => '09:00',
                ]
            );

        $response
            ->assertRedirect(
                route('admin.job-orders.index')
            )
            ->assertSessionHasErrors(
                'technician_id'
            );

        $jobOrder->refresh();

        $this->assertSame(
            'pending',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->technician_id
        );
    }

    public function test_assignment_requires_valid_future_schedule(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $customer = Customer::query()->firstOrFail();

        $technician = Technician::query()
            ->where('status', 'available')
            ->firstOrFail();

        $jobOrder = $this->createJobOrder(
            customer: $customer,
            status: 'pending'
        );

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.job-orders.index'))
            ->patch(
                route(
                    'admin.job-orders.assignment.update',
                    $jobOrder
                ),
                [
                    'assignment_job_order_id' => $jobOrder->id,
                    'technician_id' => $technician->id,
                    'scheduled_date' => now()->subDay()->toDateString(),
                    'scheduled_time' => '08:30',
                ]
            );

        $response
            ->assertRedirect(
                route('admin.job-orders.index')
            )
            ->assertSessionHasErrors(
                'scheduled_date'
            );

        $jobOrder->refresh();

        $this->assertSame(
            'pending',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->technician_id
        );

        $this->assertNull(
            $jobOrder->scheduled_date
        );

        $this->assertNull(
            $jobOrder->scheduled_time
        );
    }

    public function test_assignment_rejects_same_day_time_that_has_already_passed(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                10,
                5,
                15,
                0,
                0
            )
        );

        try {
            $this->seed(DemoDataSeeder::class);

            $admin = $this->createActiveUser('admin');

            $customer = Customer::query()->firstOrFail();

            $technician = Technician::query()
                ->where('status', 'available')
                ->whereHas(
                    'user',
                    fn ($query) => $query
                        ->where('role', 'technician')
                        ->where('account_status', 'active')
                )
                ->firstOrFail();

            $jobOrder = $this->createJobOrder(
                customer: $customer,
                status: 'pending'
            );

            $response = $this
                ->actingAs($admin)
                ->from(route('admin.job-orders.index'))
                ->patch(
                    route(
                        'admin.job-orders.assignment.update',
                        $jobOrder
                    ),
                    [
                        'assignment_job_order_id' => $jobOrder->id,
                        'technician_id' => $technician->id,
                        'scheduled_date' => now()->toDateString(),
                        'scheduled_time' => '14:59',
                    ]
                );

            $response
                ->assertRedirect(
                    route('admin.job-orders.index')
                )
                ->assertSessionHasErrors(
                    'scheduled_time'
                );

            $jobOrder->refresh();

            $this->assertSame(
                'pending',
                $jobOrder->status
            );

            $this->assertNull(
                $jobOrder->technician_id
            );

            $this->assertNull(
                $jobOrder->scheduled_date
            );

            $this->assertNull(
                $jobOrder->scheduled_time
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_in_progress_completed_and_cancelled_job_orders_cannot_be_reassigned(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $customer = Customer::query()->firstOrFail();

        $originalTechnician = Technician::query()
            ->where('status', 'available')
            ->firstOrFail();

        $replacementTechnician =
            $this->createTechnician(
                status: 'available',
                accountStatus: 'active',
                code: 'TECH-LOCK-0002'
            );

        foreach (
            [
                'in_progress',
                'completed',
                'cancelled',
            ] as $status
        ) {
            $originalDate = now()
                ->addDay()
                ->toDateString();

            $jobOrder = $this->createJobOrder(
                customer: $customer,
                status: $status,
                technician: $originalTechnician,
                scheduledDate: $originalDate,
                scheduledTime: '08:00'
            );

            $response = $this
                ->actingAs($admin)
                ->from(route('admin.job-orders.index'))
                ->patch(
                    route(
                        'admin.job-orders.assignment.update',
                        $jobOrder
                    ),
                    [
                        'assignment_job_order_id' => $jobOrder->id,
                        'technician_id' => $replacementTechnician->id,
                        'scheduled_date' => now()
                            ->addDays(3)
                            ->toDateString(),
                        'scheduled_time' => '14:00',
                    ]
                );

            $response
                ->assertRedirect(
                    route('admin.job-orders.index')
                )
                ->assertSessionHasErrors(
                    'assignment'
                );

            $jobOrder->refresh();

            $this->assertSame(
                $status,
                $jobOrder->status
            );

            $this->assertSame(
                $originalTechnician->id,
                $jobOrder->technician_id
            );

            $this->assertSame(
                $originalDate,
                $jobOrder->scheduled_date?->toDateString()
            );

            $this->assertSame(
                '08:00',
                substr(
                    (string) $jobOrder->scheduled_time,
                    0,
                    5
                )
            );
        }
    }

    public function test_assignment_ui_only_lists_active_available_technicians(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $availableTechnician =
            $this->createTechnician(
                status: 'available',
                accountStatus: 'active',
                code: 'TECH-ELIGIBLE'
            );

        $unavailableTechnician =
            $this->createTechnician(
                status: 'unavailable',
                accountStatus: 'active',
                code: 'TECH-NOT-AVAILABLE'
            );

        $inactiveTechnician =
            $this->createTechnician(
                status: 'available',
                accountStatus: 'inactive',
                code: 'TECH-NOT-ACTIVE'
            );

        $response = $this
            ->actingAs($admin)
            ->get(
                route('admin.job-orders.index')
            );

        $response
            ->assertOk()
            ->assertSee(
                $availableTechnician->technician_code
            )
            ->assertDontSee(
                $unavailableTechnician->technician_code
            )
            ->assertDontSee(
                $inactiveTechnician->technician_code
            )
            ->assertSee(
                'Select an available technician'
            );
    }

    private function createActiveUser(
        string $role
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }

    private function createTechnician(
        string $status,
        string $accountStatus,
        string $code
    ): Technician {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => 'technician',
            'account_status' => $accountStatus,
        ])->save();

        return Technician::query()->create([
            'user_id' => $user->id,
            'technician_code' => $code,
            'specialization' => 'Fiber Installation and Repair',
            'status' => $status,
        ]);
    }

    private function createJobOrder(
        Customer $customer,
        string $status,
        ?Technician $technician = null,
        ?string $scheduledDate = null,
        ?string $scheduledTime = null
    ): JobOrder {
        return JobOrder::query()->create([
            'service_request_id' => null,
            'customer_id' => $customer->id,
            'technician_id' => $technician?->id,
            'job_order_number' => 'JO-TEST-'.Str::uuid()->toString(),
            'job_type' => 'repair',
            'description' => 'Feature 2 technician assignment test Job Order.',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'status' => $status,
        ]);
    }
}
