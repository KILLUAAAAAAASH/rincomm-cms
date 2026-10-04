<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianJobOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_technician_can_start_owned_job_order(): void
    {
        $this->seed(DemoDataSeeder::class);

        $technicianUser = User::query()
            ->where('email', 'technician@rincomm.test')
            ->firstOrFail();

        $jobOrder = JobOrder::query()
            ->where('job_order_number', 'JO-0001')
            ->firstOrFail();

        /*
         * JO-0001 predates the Module 7 job_type field in the demo data.
         * Set the known demo record explicitly so the production workflow
         * validation can be exercised.
         */
        $jobOrder->forceFill([
            'job_type' => 'repair',
            'status' => 'assigned',
            'started_at' => null,
            'completed_at' => null,
        ])->save();

        $response = $this
            ->actingAs($technicianUser)
            ->patch(
                route(
                    'technician.job-orders.start',
                    $jobOrder
                )
            );

        $response->assertRedirect(
            route(
                'technician.job-orders.show',
                $jobOrder
            )
        );

        $response->assertSessionHas(
            'success',
            'Job Order started successfully.'
        );

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );

        $this->assertNotNull(
            $jobOrder->started_at
        );

        $this->assertNull(
            $jobOrder->completed_at
        );
    }
}
