<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use Tests\TestCase;

class GatewayLivemodeReceiptTest extends TestCase
{
    public function test_sandbox_gcash_receipt_shows_discreet_demo_disclosure(): void
    {
        $html = $this->renderReceipt('gcash', false);

        $this->assertStringContainsString('Demo transaction (PayMongo test mode)', $html);
        $this->assertStringContainsString('No actual funds transferred', $html);
        $this->assertStringNotContainsString('TEST PAYMENT', $html);
        $this->assertStringNotContainsString('NO REAL FUNDS RECEIVED', $html);
    }

    public function test_live_gcash_receipt_has_no_sandbox_warning(): void
    {
        $html = $this->renderReceipt('gcash', true);

        $this->assertStringNotContainsString('TEST PAYMENT', $html);
        $this->assertStringNotContainsString('MODE UNVERIFIED', $html);
    }

    public function test_unverified_gcash_receipt_discloses_unknown_mode(): void
    {
        $html = $this->renderReceipt('gcash', null);

        $this->assertStringContainsString('MODE UNVERIFIED', $html);
        $this->assertStringNotContainsString('NO REAL FUNDS RECEIVED', $html);
    }

    public function test_cash_receipt_has_no_paymongo_mode_warning(): void
    {
        $html = $this->renderReceipt('cash', null);

        $this->assertStringNotContainsString('TEST PAYMENT', $html);
        $this->assertStringNotContainsString('MODE UNVERIFIED', $html);
    }

    public function test_payment_mode_cast_preserves_false_true_and_null(): void
    {
        $payment = new Payment;
        $payment->gateway_livemode = false;
        $this->assertSame(false, $payment->gateway_livemode);
        $payment->gateway_livemode = true;
        $this->assertSame(true, $payment->gateway_livemode);
        $payment->gateway_livemode = null;
        $this->assertNull($payment->gateway_livemode);
    }

    private function renderReceipt(string $method, ?bool $livemode): string
    {
        $customer = new Customer;
        $customer->first_name = 'Test';
        $customer->last_name = 'Subscriber';
        $customer->customer_code = 'CUST-TEST';

        $invoice = new Invoice;
        $invoice->id = 10;
        $invoice->invoice_number = 'INV-TEST';
        $invoice->setRelation('customer', $customer);

        $payment = new Payment;
        $payment->payment_method = $method;
        $payment->gateway_livemode = $livemode;
        $payment->payment_reference = 'PAY-TEST';
        $payment->paid_at = now();

        $receipt = new PaymentReceipt;
        $receipt->receipt_number = 'RIN-ER-TEST';
        $receipt->payment_method = $method;
        $receipt->amount_paid = '149.00';
        $receipt->issued_at = now();
        $receipt->issued_by = 'System';

        return view('shared.receipts.show', compact('invoice', 'payment', 'receipt'))->render();
    }
}