<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use App\Models\JobOrderProof;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicianJobOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_technician_can_start_owned_job_order(): void
    {
        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();
        $jobOrder = $this->demoJobOrder();

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
            route('technician.job-orders.index')
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

    public function test_in_progress_technician_can_submit_completion_report_and_complete_owned_job_order(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $startedAt = $jobOrder->started_at?->copy();

        $proof = $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $response = $this
            ->actingAs($technicianUser)
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => '   Replaced the damaged drop cable, restored the connection, and verified stable service.   ',
                ]
            );

        $response->assertRedirect(
            route('technician.job-orders.index')
        );

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas(
            'success',
            'Job Order completed successfully.'
        );

        $jobOrder->refresh();

        $this->assertSame(
            'completed',
            $jobOrder->status
        );

        $this->assertSame(
            'Replaced the damaged drop cable, restored the connection, and verified stable service.',
            $jobOrder->completion_report
        );

        $this->assertNotNull(
            $jobOrder->completed_at
        );

        $this->assertNotNull(
            $startedAt
        );

        $this->assertTrue(
            $startedAt->equalTo(
                $jobOrder->started_at
            )
        );

        $this->assertDatabaseHas(
            'job_order_proofs',
            [
                'id' => $proof->id,
                'job_order_id' => $jobOrder->id,
            ]
        );

        Storage::disk('local')->assertExists(
            $proof->file_path
        );
    }

    public function test_blank_completion_report_is_rejected_without_completing_job_order(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route('technician.job-orders.index')
            )
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => '   ',
                ]
            );

        $response->assertRedirect(
            route('technician.job-orders.index')
        );

        $response->assertSessionHasErrors([
            'completion_report',
        ]);

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->completed_at
        );

        $this->assertNull(
            $jobOrder->completion_report
        );
    }

    public function test_completion_report_larger_than_five_thousand_characters_is_rejected(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route('technician.job-orders.index')
            )
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => str_repeat(
                        'A',
                        5001
                    ),
                ]
            );

        $response->assertRedirect(
            route('technician.job-orders.index')
        );

        $response->assertSessionHasErrors([
            'completion_report',
        ]);

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->completed_at
        );

        $this->assertNull(
            $jobOrder->completion_report
        );
    }

    public function test_job_order_cannot_be_completed_without_proof_of_work(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route('technician.job-orders.index')
            )
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => 'Repair completed and connection verified.',
                ]
            );

        $response->assertRedirect(
            route('technician.job-orders.index')
        );

        $response->assertSessionHasErrors([
            'completion_report',
        ]);

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->completed_at
        );

        $this->assertNull(
            $jobOrder->completion_report
        );
    }

    public function test_job_order_without_job_type_cannot_be_completed(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $jobOrder->forceFill([
            'job_type' => null,
        ])->save();

        $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route('technician.job-orders.index')
            )
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => 'Field work completed.',
                ]
            );

        $response->assertRedirect(
            route('technician.job-orders.index')
        );

        $response->assertSessionHasErrors([
            'completion_report',
        ]);

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->completed_at
        );

        $this->assertNull(
            $jobOrder->completion_report
        );
    }

    public function test_technician_cannot_complete_job_order_assigned_to_another_technician(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $otherUser = User::factory()->create();

        $otherUser->forceFill([
            'role' => 'technician',
            'account_status' => 'active',
        ])->save();

        $otherTechnician = Technician::query()->create([
            'user_id' => $otherUser->id,
            'technician_code' => 'TECH-0100',
            'specialization' => 'Fiber Installation',
            'status' => 'available',
        ]);

        $jobOrder->forceFill([
            'technician_id' => $otherTechnician->id,
        ])->save();

        $response = $this
            ->actingAs($technicianUser)
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => '',
                ]
            );

        $response->assertNotFound();

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->completed_at
        );

        $this->assertNull(
            $jobOrder->completion_report
        );
    }

    public function test_assigned_job_order_cannot_be_completed_before_it_is_started(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'assigned'
        );

        $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route('technician.job-orders.index')
            )
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => 'Attempted completion before start.',
                ]
            );

        $response->assertRedirect(
            route('technician.job-orders.index')
        );

        $response->assertSessionHasErrors([
            'completion_report',
        ]);

        $jobOrder->refresh();

        $this->assertSame(
            'assigned',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->completed_at
        );

        $this->assertNull(
            $jobOrder->completion_report
        );
    }

    public function test_completed_job_order_cannot_be_completed_again_or_overwrite_report(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'completed'
        );

        $originalCompletedAt = now()->subMinutes(10)->startOfSecond();

        $jobOrder->forceFill([
            'completed_at' => $originalCompletedAt,
            'completion_report' => 'Original final completion report.',
        ])->save();

        $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route('technician.job-orders.index')
            )
            ->patch(
                route(
                    'technician.job-orders.complete',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion',
                    'completion_job_order_id' => $jobOrder->id,
                    'completion_report' => 'Attempt to overwrite the report.',
                ]
            );

        $response->assertRedirect(
            route('technician.job-orders.index')
        );

        $response->assertSessionHasErrors([
            'completion_report',
        ]);

        $jobOrder->refresh();

        $this->assertSame(
            'completed',
            $jobOrder->status
        );

        $this->assertSame(
            'Original final completion report.',
            $jobOrder->completion_report
        );

        $this->assertTrue(
            $originalCompletedAt->equalTo(
                $jobOrder->completed_at
            )
        );
    }

    public function test_in_progress_technician_can_upload_proof_without_changing_job_status(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $response = $this
            ->actingAs($technicianUser)
            ->post(
                route(
                    'technician.job-orders.proofs.store',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion_proof',
                    'proof_job_order_id' => $jobOrder->id,
                    'proof' => $this->validProofFile(),
                    'notes' => 'Fiber termination completed and tested.',
                ]
            );

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas(
            'success',
            'Proof of work uploaded successfully.'
        );

        $response->assertSessionHas(
            'proof_job_order_id',
            (string) $jobOrder->id
        );

        $this->assertDatabaseCount(
            'job_order_proofs',
            1
        );

        $proof = JobOrderProof::query()
            ->firstOrFail();

        $this->assertSame(
            $jobOrder->id,
            $proof->job_order_id
        );

        $this->assertSame(
            $technicianUser->id,
            $proof->uploaded_by
        );

        $this->assertSame(
            'proof.png',
            $proof->original_name
        );

        $this->assertSame(
            'Fiber termination completed and tested.',
            $proof->notes
        );

        $this->assertGreaterThan(
            0,
            $proof->file_size
        );

        $this->assertStringStartsWith(
            "job-order-proofs/{$jobOrder->id}/",
            $proof->file_path
        );

        Storage::disk('local')->assertExists(
            $proof->file_path
        );

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );

        $this->assertNull(
            $jobOrder->completed_at
        );
    }

    public function test_proof_upload_is_rejected_before_job_is_in_progress_and_temporary_file_is_cleaned_up(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'assigned'
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route(
                    'technician.job-orders.index'
                )
            )
            ->post(
                route(
                    'technician.job-orders.proofs.store',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion_proof',
                    'proof_job_order_id' => $jobOrder->id,
                    'proof' => $this->validProofFile(),
                ]
            );

        $response->assertRedirect(
            route(
                'technician.job-orders.index'
            )
        );

        $response->assertSessionHasErrors([
            'proof',
        ]);

        $this->assertDatabaseCount(
            'job_order_proofs',
            0
        );

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );

        $jobOrder->refresh();

        $this->assertSame(
            'assigned',
            $jobOrder->status
        );
    }

    public function test_technician_cannot_upload_proof_to_job_order_assigned_to_another_technician(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $otherUser = User::factory()->create();

        $otherUser->forceFill([
            'role' => 'technician',
            'account_status' => 'active',
        ])->save();

        $otherTechnician = Technician::query()->create([
            'user_id' => $otherUser->id,
            'technician_code' => 'TECH-0099',
            'specialization' => 'Network Repair',
            'status' => 'available',
        ]);

        $jobOrder->forceFill([
            'technician_id' => $otherTechnician->id,
        ])->save();

        $response = $this
            ->actingAs($technicianUser)
            ->post(
                route(
                    'technician.job-orders.proofs.store',
                    $jobOrder
                ),
                [
                    'proof' => $this->validProofFile(),
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseCount(
            'job_order_proofs',
            0
        );

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    public function test_invalid_proof_type_is_rejected_without_creating_a_record(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route(
                    'technician.job-orders.index'
                )
            )
            ->post(
                route(
                    'technician.job-orders.proofs.store',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion_proof',
                    'proof_job_order_id' => $jobOrder->id,
                    'proof' => UploadedFile::fake()
                        ->createWithContent(
                            'script.php',
                            '<?php echo "not allowed";'
                        ),
                ]
            );

        $response->assertRedirect(
            route(
                'technician.job-orders.index'
            )
        );

        $response->assertSessionHasErrors([
            'proof',
        ]);

        $this->assertDatabaseCount(
            'job_order_proofs',
            0
        );

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    public function test_proof_larger_than_five_megabytes_is_rejected(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route(
                    'technician.job-orders.index'
                )
            )
            ->post(
                route(
                    'technician.job-orders.proofs.store',
                    $jobOrder
                ),
                [
                    'modal_context' => 'completion_proof',
                    'proof_job_order_id' => $jobOrder->id,
                    'proof' => UploadedFile::fake()->create(
                        'large-proof.pdf',
                        6000,
                        'application/pdf'
                    ),
                ]
            );

        $response->assertRedirect(
            route(
                'technician.job-orders.index'
            )
        );

        $response->assertSessionHasErrors([
            'proof',
        ]);

        $this->assertDatabaseCount(
            'job_order_proofs',
            0
        );
    }

    public function test_assigned_technician_can_view_and_download_private_proof(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'completed'
        );

        $proof = $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $viewResponse = $this
            ->actingAs($technicianUser)
            ->get(
                route(
                    'technician.job-orders.proofs.show',
                    [
                        $jobOrder,
                        $proof,
                    ]
                )
            );

        $viewResponse->assertOk();

        $downloadResponse = $this
            ->actingAs($technicianUser)
            ->get(
                route(
                    'technician.job-orders.proofs.download',
                    [
                        $jobOrder,
                        $proof,
                    ]
                )
            );

        $downloadResponse->assertOk();

        Storage::disk('local')->assertExists(
            $proof->file_path
        );
    }

    public function test_proof_cannot_be_accessed_through_a_different_job_order(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $proof = $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $otherJobOrder = $jobOrder->replicate();

        $otherJobOrder->forceFill([
            'job_order_number' => 'JO-PROOF-OTHER',
        ]);

        $otherJobOrder->save();

        $response = $this
            ->actingAs($technicianUser)
            ->get(
                route(
                    'technician.job-orders.proofs.show',
                    [
                        $otherJobOrder,
                        $proof,
                    ]
                )
            );

        $response->assertNotFound();
    }

    public function test_in_progress_technician_can_delete_proof_and_private_file(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $proof = $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        Storage::disk('local')->assertExists(
            $proof->file_path
        );

        $response = $this
            ->actingAs($technicianUser)
            ->delete(
                route(
                    'technician.job-orders.proofs.destroy',
                    [
                        $jobOrder,
                        $proof,
                    ]
                ),
                [
                    'modal_context' => 'completion_proof',
                    'proof_job_order_id' => $jobOrder->id,
                ]
            );

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas(
            'success',
            'Proof of work deleted successfully.'
        );

        $response->assertSessionHas(
            'proof_job_order_id',
            (string) $jobOrder->id
        );

        $this->assertDatabaseMissing(
            'job_order_proofs',
            [
                'id' => $proof->id,
            ]
        );

        Storage::disk('local')->assertMissing(
            $proof->file_path
        );

        $jobOrder->refresh();

        $this->assertSame(
            'in_progress',
            $jobOrder->status
        );
    }

    public function test_completed_job_proof_cannot_be_deleted(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'completed'
        );

        $proof = $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $response = $this
            ->actingAs($technicianUser)
            ->from(
                route(
                    'technician.job-orders.index'
                )
            )
            ->delete(
                route(
                    'technician.job-orders.proofs.destroy',
                    [
                        $jobOrder,
                        $proof,
                    ]
                ),
                [
                    'modal_context' => 'completion_proof',
                    'proof_job_order_id' => $jobOrder->id,
                ]
            );

        $response->assertRedirect(
            route(
                'technician.job-orders.index'
            )
        );

        $response->assertSessionHasErrors([
            'proof',
        ]);

        $this->assertDatabaseHas(
            'job_order_proofs',
            [
                'id' => $proof->id,
                'job_order_id' => $jobOrder->id,
            ]
        );

        Storage::disk('local')->assertExists(
            $proof->file_path
        );

        $jobOrder->refresh();

        $this->assertSame(
            'completed',
            $jobOrder->status
        );
    }

    public function test_job_orders_index_renders_simplified_modal_sequence_and_completed_jobs_are_read_only(): void
    {
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $technicianUser = $this->demoTechnicianUser();

        $jobOrder = $this->prepareDemoJobOrder(
            'in_progress'
        );

        $proof = $this->createStoredProof(
            $jobOrder,
            $technicianUser
        );

        $inProgressResponse = $this
            ->actingAs($technicianUser)
            ->get(
                route(
                    'technician.job-orders.index'
                )
            );

        $inProgressResponse->assertOk();

        $inProgressResponse->assertSee(
            'data-job-modal-open="'.$jobOrder->id.'"',
            false
        );

        $inProgressResponse->assertSee(
            'data-job-modal="'.$jobOrder->id.'"',
            false
        );

        $inProgressResponse->assertSee(
            'Submit Completion Report'
        );

        $inProgressResponse->assertSee(
            'data-completion-modal="'.$jobOrder->id.'"',
            false
        );

        $inProgressResponse->assertSee(
            'Proof of Work'
        );

        $inProgressResponse->assertSee(
            'Add Proof'
        );

        $inProgressResponse->assertSee(
            'data-proof-preview-modal',
            false
        );

        $inProgressResponse->assertSee(
            'data-proof-preview-open',
            false
        );

        $inProgressResponse->assertSee(
            'data-proof-delete-action="',
            false
        );

        $inProgressResponse->assertSee(
            $proof->original_name
        );

        $inProgressResponse->assertDontSee(
            'data-proof-modal=',
            false
        );

        $inProgressResponse->assertDontSee(
            'Manage Proof'
        );

        $inProgressResponse->assertDontSee(
            'target="_blank"',
            false
        );

        $completionReport =
            'Repair completed, connection tested, and service restored.';

        $jobOrder->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_report' => $completionReport,
        ])->save();

        $completedResponse = $this
            ->actingAs($technicianUser)
            ->get(
                route(
                    'technician.job-orders.index'
                )
            );

        $completedResponse->assertOk();

        $completedResponse->assertSee(
            'data-job-modal="'.$jobOrder->id.'"',
            false
        );

        $completedResponse->assertSee(
            'Completion Report'
        );

        $completedResponse->assertSee(
            $completionReport
        );

        $completedResponse->assertSee(
            'Proof of Work'
        );

        $completedResponse->assertSee(
            $proof->original_name
        );

        $completedResponse->assertSee(
            'data-proof-preview-open',
            false
        );

        $completedResponse->assertSee(
            'data-proof-preview-modal',
            false
        );

        $completedResponse->assertDontSee(
            'data-completion-modal="'.$jobOrder->id.'"',
            false
        );

        $completedResponse->assertDontSee(
            'name="proof"',
            false
        );

        $completedResponse->assertDontSee(
            'data-proof-delete-action="',
            false
        );

        $completedResponse->assertDontSee(
            'Submit Completion Report'
        );
    }

    private function demoTechnicianUser(): User
    {
        return User::query()
            ->where(
                'email',
                'technician@rincomm.test'
            )
            ->firstOrFail();
    }

    private function demoJobOrder(): JobOrder
    {
        return JobOrder::query()
            ->where(
                'job_order_number',
                'JO-0001'
            )
            ->firstOrFail();
    }

    private function prepareDemoJobOrder(
        string $status
    ): JobOrder {
        $jobOrder = $this->demoJobOrder();

        $jobOrder->forceFill([
            'job_type' => 'repair',
            'status' => $status,
            'started_at' => in_array(
                $status,
                [
                    'in_progress',
                    'completed',
                ],
                true
            )
                ? now()->subHour()
                : null,
            'completed_at' => $status === 'completed'
                ? now()
                : null,
            'completion_report' => null,
        ])->save();

        return $jobOrder->fresh();
    }

    private function validProofFile(): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );

        if ($png === false) {
            $this->fail(
                'The proof fixture could not be decoded.'
            );
        }

        return UploadedFile::fake()
            ->createWithContent(
                'proof.png',
                $png
            );
    }

    private function createStoredProof(
        JobOrder $jobOrder,
        User $uploader
    ): JobOrderProof {
        $path =
            "job-order-proofs/{$jobOrder->id}/existing-proof.png";

        Storage::disk('local')->put(
            $path,
            'private-proof-content'
        );

        return $jobOrder
            ->proofs()
            ->create([
                'uploaded_by' => $uploader->id,
                'original_name' => 'existing-proof.png',
                'file_path' => $path,
                'mime_type' => 'image/png',
                'file_size' => strlen(
                    'private-proof-content'
                ),
                'notes' => 'Existing proof.',
            ]);
    }
}
