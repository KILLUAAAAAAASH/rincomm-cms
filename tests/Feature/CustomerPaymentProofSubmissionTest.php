<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentProofSubmission;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerPaymentProofSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function invoiceForCustomer(): array
    {
        $this->seed(DemoDataSeeder::class);
        $customer = Customer::query()->with('user')->firstOrFail();
        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();
        $invoice = Invoice::query()->create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-PROOF-TEST-'.uniqid(),
            'billing_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'subtotal' => '1500.00',
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => '1500.00',
            'status' => 'issued',
        ]);
        return [$customer, $invoice];
    }

    public function test_customer_can_submit_private_proof_without_marking_invoice_paid(): void
    {
        Storage::fake('local');
        [$customer, $invoice] = $this->invoiceForCustomer();

        $this->actingAs($customer->user)->post(route('customer.billing.payment-proofs.store', $invoice), [
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-TEST-12345',
            'amount' => '350.00',
            'proof' => UploadedFile::fake()->createWithContent('transfer.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4//8/AAX+Av4N70a4AAAAAElFTkSuQmCC')),
            'customer_id' => 999999,
            'status' => 'completed',
        ])->assertRedirect(route('customer.billing.show', $invoice));

        $submission = PaymentProofSubmission::query()->sole();
        $this->assertSame($invoice->id, $submission->invoice_id);
        $this->assertSame($customer->id, $submission->customer_id);
        $this->assertSame('pending', $submission->status);
        $this->assertSame('350.00', $submission->amount);
        Storage::disk('local')->assertExists($submission->proof_path);
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_duplicate_pending_proof_is_rejected(): void
    {
        Storage::fake('local');
        [$customer, $invoice] = $this->invoiceForCustomer();
        $data = [
            'payment_method' => 'bank_transfer', 'transaction_reference' => 'BANK-TEST-12345',
            'amount' => '350.00', 'proof' => UploadedFile::fake()->createWithContent('one.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4//8/AAX+Av4N70a4AAAAAElFTkSuQmCC')),
        ];
        $url = route('customer.billing.payment-proofs.store', $invoice);
        $this->actingAs($customer->user)->post($url, $data)->assertRedirect();
        $data['proof'] = UploadedFile::fake()->createWithContent('two.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4//8/AAX+Av4N70a4AAAAAElFTkSuQmCC'));
        $this->post($url, $data)->assertSessionHasErrors('proof');
        $this->assertSame(1, PaymentProofSubmission::query()->count());
    }

    public function test_non_customer_cannot_upload_and_invalid_file_is_rejected(): void
    {
        Storage::fake('local');
        [$customer, $invoice] = $this->invoiceForCustomer();
        $url = route('customer.billing.payment-proofs.store', $invoice);
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->post($url, [])->assertStatus(403);
        $this->actingAs($customer->user)->post($url, [
            'payment_method' => 'bank_transfer', 'transaction_reference' => 'BANK-TEST-12345',
            'amount' => '350.00', 'proof' => UploadedFile::fake()->create('payload.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('proof');
        $this->assertSame(0, PaymentProofSubmission::query()->count());
    }

    public function test_amount_above_invoice_balance_is_rejected(): void
    {
        Storage::fake('local');
        [$customer, $invoice] = $this->invoiceForCustomer();
        $this->actingAs($customer->user)->post(route('customer.billing.payment-proofs.store', $invoice), [
            'payment_method' => 'bank_transfer', 'transaction_reference' => 'BANK-OVERPAY-123',
            'amount' => '1500.01', 'proof' => UploadedFile::fake()->createWithContent('transfer.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4//8/AAX+Av4N70a4AAAAAElFTkSuQmCC')),
        ])->assertSessionHasErrors('amount');
        $this->assertSame(0, PaymentProofSubmission::query()->count());
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_customer_can_see_pending_proof_on_owned_invoice(): void
    {
        [$customer, $invoice] = $this->invoiceForCustomer();
        PaymentProofSubmission::query()->create([
            'invoice_id' => $invoice->id, 'customer_id' => $customer->id,
            'payment_method' => 'gcash', 'transaction_reference' => 'GCASH-TEST-123',
            'amount' => '450.00', 'proof_path' => 'payment-proofs/private-test.jpg', 'status' => 'pending',
        ]);
        $this->actingAs($customer->user)->get(route('customer.billing.show', $invoice))
            ->assertOk()->assertSee('GCASH-TEST-123')->assertSee('Pending staff review')
            ->assertDontSee('payment-proofs/private-test.jpg');
    }
}
