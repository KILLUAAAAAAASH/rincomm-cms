<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJobOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_staff_can_access_job_order_pages(): void
    {
        $admin = $this->createActiveUser('admin');
        $staff = $this->createActiveUser('staff');

        $this->actingAs($admin)
            ->get(route('admin.job-orders.index'))
            ->assertOk()
            ->assertSee('Job Orders');

        $this->actingAs($admin)
            ->get(route('admin.job-orders.create'))
            ->assertOk()
            ->assertSee('Create Job Order');

        $this->actingAs($staff)
            ->get(route('admin.job-orders.index'))
            ->assertOk()
            ->assertSee('Job Orders');

        $this->actingAs($staff)
            ->get(route('admin.job-orders.create'))
            ->assertOk()
            ->assertSee('Create Job Order');
    }

    public function test_customer_and_technician_cannot_access_admin_job_orders(): void
    {
        $customer = $this->createActiveUser('customer');
        $technician = $this->createActiveUser('technician');

        $this->actingAs($customer)
            ->get(route('admin.job-orders.index'))
            ->assertForbidden();

        $this->actingAs($technician)
            ->get(route('admin.job-orders.index'))
            ->assertForbidden();
    }

    public function test_staff_can_create_pending_job_order_without_service_request(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser('staff');

        $customer = Customer::query()->firstOrFail();

        $response = $this
            ->actingAs($staff)
            ->post(route('admin.job-orders.store'), [
                'customer_id' => $customer->id,
                'service_request_id' => null,
                'job_type' => 'repair',
                'description' => 'Check intermittent connection and repair line if required.',
            ]);

        $jobOrder = JobOrder::query()
            ->where('customer_id', $customer->id)
            ->where('job_type', 'repair')
            ->where(
                'description',
                'Check intermittent connection and repair line if required.'
            )
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.job-orders.show', $jobOrder)
        );

        $response->assertSessionHas(
            'success',
            'Job Order created successfully.'
        );

        $this->assertSame(
            sprintf('JO-%06d', $jobOrder->id),
            $jobOrder->job_order_number
        );

        $this->assertSame('pending', $jobOrder->status);
        $this->assertNull($jobOrder->service_request_id);
        $this->assertNull($jobOrder->technician_id);
        $this->assertNull($jobOrder->scheduled_date);
        $this->assertNull($jobOrder->scheduled_time);
    }

    public function test_matching_service_request_can_be_linked_to_job_order(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $serviceRequest = ServiceRequest::query()
            ->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.job-orders.store'), [
                'customer_id' => $serviceRequest->customer_id,
                'service_request_id' => $serviceRequest->id,
                'job_type' => 'site_survey',
                'description' => 'Perform site survey for the requested field work.',
            ]);

        $jobOrder = JobOrder::query()
            ->where('service_request_id', $serviceRequest->id)
            ->where('job_type', 'site_survey')
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.job-orders.show', $jobOrder)
        );

        $this->assertSame(
            $serviceRequest->customer_id,
            $jobOrder->customer_id
        );

        $this->assertSame('pending', $jobOrder->status);
    }

    public function test_service_request_must_belong_to_selected_subscriber(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        $serviceRequest = ServiceRequest::query()
            ->firstOrFail();

        $otherCustomerUser = $this->createActiveUser('customer');

        $otherCustomer = Customer::query()->create([
            'user_id' => $otherCustomerUser->id,
            'customer_code' => 'CUST-TEST-0002',
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Subscriber',
            'phone' => '09999999999',
            'email' => $otherCustomerUser->email,
            'address' => 'Test Address',
            'city' => 'Paniqui',
            'province' => 'Tarlac',
            'postal_code' => '2307',
            'billing_address' => 'Test Address, Paniqui, Tarlac',
            'installation_address' => 'Test Address, Paniqui, Tarlac',
            'status' => 'active',
        ]);

        $beforeCount = JobOrder::query()->count();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.job-orders.create'))
            ->post(route('admin.job-orders.store'), [
                'customer_id' => $otherCustomer->id,
                'service_request_id' => $serviceRequest->id,
                'job_type' => 'line_maintenance',
                'description' => 'Inspect and maintain affected line.',
            ]);

        $response
            ->assertRedirect(route('admin.job-orders.create'))
            ->assertSessionHasErrors('service_request_id');

        $this->assertSame(
            $beforeCount,
            JobOrder::query()->count()
        );
    }

    public function test_all_required_job_order_types_can_be_created(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');
        $customer = Customer::query()->firstOrFail();

        $jobTypes = [
            'new_installation',
            'site_survey',
            'repair',
            'line_maintenance',
            'physical_disconnection',
        ];

        foreach ($jobTypes as $jobType) {
            $description = sprintf(
                'FRS verification for %s Job Order.',
                str_replace('_', ' ', $jobType)
            );

            $response = $this
                ->actingAs($admin)
                ->post(route('admin.job-orders.store'), [
                    'customer_id' => $customer->id,
                    'service_request_id' => null,
                    'job_type' => $jobType,
                    'description' => $description,
                ]);

            $jobOrder = JobOrder::query()
                ->where('customer_id', $customer->id)
                ->where('job_type', $jobType)
                ->where('description', $description)
                ->latest('id')
                ->firstOrFail();

            $response
                ->assertRedirect(
                    route('admin.job-orders.show', $jobOrder)
                )
                ->assertSessionHas(
                    'success',
                    'Job Order created successfully.'
                );

            $this->assertSame(
                sprintf('JO-%06d', $jobOrder->id),
                $jobOrder->job_order_number
            );

            $this->assertSame($jobType, $jobOrder->job_type);
            $this->assertSame('pending', $jobOrder->status);
            $this->assertNull($jobOrder->service_request_id);
            $this->assertNull($jobOrder->technician_id);
            $this->assertNull($jobOrder->scheduled_date);
            $this->assertNull($jobOrder->scheduled_time);
        }
    }

    public function test_job_order_creation_requires_valid_core_fields(): void
    {
        $admin = $this->createActiveUser('admin');

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.job-orders.create'))
            ->post(route('admin.job-orders.store'), [
                'customer_id' => '',
                'service_request_id' => '',
                'job_type' => 'invalid_type',
                'description' => '',
            ]);

        $response
            ->assertRedirect(route('admin.job-orders.create'))
            ->assertSessionHasErrors([
                'customer_id',
                'job_type',
                'description',
            ]);

        $this->assertDatabaseCount('job_orders', 0);
    }

    private function createActiveUser(string $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }
}
