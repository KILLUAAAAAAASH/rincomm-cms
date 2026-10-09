<?php

namespace Tests\Unit;

use App\Services\InvoiceBalanceService;
use App\Services\MoneyService;
use PHPUnit\Framework\TestCase;

class InvoiceBalanceServiceTest extends TestCase
{
    private InvoiceBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InvoiceBalanceService(
            new MoneyService()
        );
    }

    public function test_calculates_balance_with_no_completed_payments(): void
    {
        $result = $this->service->calculate(
            '1500.00',
            []
        );

        $this->assertSame(
            150000,
            $result['total_cents']
        );

        $this->assertSame(
            0,
            $result['paid_cents']
        );

        $this->assertSame(
            150000,
            $result['balance_cents']
        );

        $this->assertSame(
            '1500.00',
            $result['total_amount']
        );

        $this->assertSame(
            '0.00',
            $result['paid_amount']
        );

        $this->assertSame(
            '1500.00',
            $result['balance_amount']
        );
    }

    public function test_sums_multiple_completed_payment_amounts(): void
    {
        $result = $this->service->calculate(
            '1500.00',
            [
                '500.00',
                '250.50',
                '149.50',
            ]
        );

        $this->assertSame(
            90000,
            $result['paid_cents']
        );

        $this->assertSame(
            60000,
            $result['balance_cents']
        );

        $this->assertSame(
            '900.00',
            $result['paid_amount']
        );

        $this->assertSame(
            '600.00',
            $result['balance_amount']
        );
    }

    public function test_full_payment_produces_zero_balance(): void
    {
        $result = $this->service->calculate(
            '1500.00',
            [
                '1000.00',
                '500.00',
            ]
        );

        $this->assertSame(
            150000,
            $result['paid_cents']
        );

        $this->assertSame(
            0,
            $result['balance_cents']
        );

        $this->assertSame(
            '0.00',
            $result['balance_amount']
        );
    }

    public function test_balance_is_clamped_to_zero_when_payments_exceed_total(): void
    {
        $result = $this->service->calculate(
            '1500.00',
            [
                '1000.00',
                '750.00',
            ]
        );

        $this->assertSame(
            175000,
            $result['paid_cents']
        );

        $this->assertSame(
            0,
            $result['balance_cents']
        );

        $this->assertSame(
            '0.00',
            $result['balance_amount']
        );
    }
}
