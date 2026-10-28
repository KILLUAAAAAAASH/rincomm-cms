<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentProofSubmission;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffPaymentProofReviewTest extends TestCase
{
    use RefreshDatabase;

    private function setupProof(): array
    {
        Storage::fake('local');
        $this->seed(DemoDataSeeder::class);
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = Customer::query()->with('user')->firstOrFail();
        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();
        $invoice = Invoice::query()->create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-STAFF-PROOF-'.uniqid(),
            'billing_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'subtotal' => '1500.00',
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => '1500.00',
            'status' => 'issued',
        ]);
        $path = 'payment-proofs/test-transfer.jpg';
        Storage::disk('local')->put($path, 'fixture-proof-file');
        $proof = PaymentProofSubmission::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-STAGE2-'.uniqid(),
            'amount' => '500.00',
            'proof_path' => $path,
            'status' => 'pending',
        ]);
        return [$admin, $customer, $invoice, $proof];
    }

    public function test_staff_invoice_shows_pending_proof_and_private_download(): void
    {
        [$admin, $customer, $invoice, $proof] = $this->setupProof();
        $this->actingAs($admin)->get(route('admin.invoices.show', $invoice))
            ->assertOk()->assertSee('Payment proof review')->assertSee($proof->transaction_reference);
        $this->actingAs($admin)->get(route('admin.invoices.payment-proofs.download', [$invoice, $proof]))
            ->assertOk()->assertHeader('Content-Disposition');
        $this->actingAs($customer->user)->get(route('admin.invoices.payment-proofs.download', [$invoice, $proof]))
            ->assertForbidden();
    }

    public function test_customer_cannot_approve_proof(): void
    {
        [$admin, $customer, $invoice, $proof] = $this->setupProof();
        $this->actingAs($customer->user)->post(route('admin.invoices.payment-proofs.review', [$invoice, $proof]), [
            'decision' => 'approve', 'confirmed_received' => 1, 'review_notes' => 'Verified in bank records.',
        ])->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $proof->fresh()->status);
    }

    public function test_approval_requires_external_confirmation(): void
    {
        [$admin, $customer, $invoice, $proof] = $this->setupProof();
        $this->actingAs($admin)->post(route('admin.invoices.payment-proofs.review', [$invoice, $proof]), [
            'decision' => 'approve', 'review_notes' => 'Not confirmed at bank.',
        ])->assertSessionHasErrors('review');
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $proof->fresh()->status);
    }

    public function test_staff_rejection_does_not_post_payment(): void
    {
        [$admin, $customer, $invoice, $proof] = $this->setupProof();
        $this->actingAs($admin)->post(route('admin.invoices.payment-proofs.review', [$invoice, $proof]), [
            'decision' => 'reject', 'review_notes' => 'Reference not found in bank records.',
        ])->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertSame('rejected', $proof->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'billing.payment_proof_rejected']);
    }

    public function test_approved_bank_transfer_records_one_payment_receipt_and_cannot_repeat(): void
    {
        [$admin, $customer, $invoice, $proof] = $this->setupProof();
        $url = route('admin.invoices.payment-proofs.review', [$invoice, $proof]);
        $data = ['decision' => 'approve', 'confirmed_received' => '1',
            'review_notes' => 'Verified deposit against bank records.'];
        $this->actingAs($admin)->post($url, $data)
            ->assertRedirect(route('admin.invoices.show', $invoice))->assertSessionHas('success');
        $this->assertSame('approved', $proof->fresh()->status);
        $this->assertSame($admin->id, $proof->fresh()->reviewed_by_user_id);
        $this->assertSame('partially_paid', $invoice->fresh()->status);
        $payment = Payment::query()->sole();
        $this->assertSame('bank_transfer', $payment->payment_method);
        $this->assertSame('completed', $payment->payment_status);
        $this->assertSame('500.00', $payment->amount);
        $this->assertSame($payment->id, $proof->fresh()->payment_id);
        $this->assertNotNull($payment->receipt);
        $this->assertDatabaseHas('activity_logs', ['action' => 'billing.payment_proof_approved']);
        $this->post($url, $data)->assertSessionHasErrors('review');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_pending_gateway_checkout_blocks_approval(): void
    {
        [$admin, $customer, $invoice, $proof] = $this->setupProof();
        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'payment_reference' => 'GATEWAY-PENDING-STAGE2',
            'amount' => '100.00',
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
        ]);
        $this->actingAs($admin)->post(route('admin.invoices.payment-proofs.review', [$invoice, $proof]), [
            'decision' => 'approve', 'confirmed_received' => '1',
            'review_notes' => 'Verified deposit against bank records.',
        ])->assertSessionHasErrors('review');
        $this->assertSame('pending', $proof->fresh()->status);
        $this->assertSame(0, Payment::query()->where('payment_status', 'completed')->count());
    }
}
