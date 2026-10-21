<?php

namespace Tests\Feature;

use App\Services\BillingCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCycleNextDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_monthly_billing_date(): void
    {
        $date = app(BillingCycleService::class)
            ->nextBillingDateAfter('2026-09-15', '2026-10-20');

        $this->assertSame('2026-11-15', $date->toDateString());
    }

    public function test_change_on_billing_day_uses_next_cycle(): void
    {
        $date = app(BillingCycleService::class)
            ->nextBillingDateAfter('2026-09-15', '2026-10-15');

        $this->assertSame('2026-11-15', $date->toDateString());
    }

    public function test_month_end_dates_are_preserved(): void
    {
        $service = app(BillingCycleService::class);

        $this->assertSame(
            '2026-02-28',
            $service->nextBillingDateAfter(
                '2026-01-31',
                '2026-02-10'
            )->toDateString()
        );

        $this->assertSame(
            '2026-03-31',
            $service->nextBillingDateAfter(
                '2026-01-31',
                '2026-02-28'
            )->toDateString()
        );
    }

    public function test_two_month_billing_cycle(): void
    {
        $service = app(BillingCycleService::class);

        $this->assertSame(
            '2026-03-31',
            $service->nextBillingDateAfter(
                '2026-01-31',
                '2026-03-01',
                2
            )->toDateString()
        );

        $this->assertSame(
            '2026-05-31',
            $service->nextBillingDateAfter(
                '2026-01-31',
                '2026-03-31',
                2
            )->toDateString()
        );
    }

    public function test_future_activation_uses_first_billing_date(): void
    {
        $date = app(BillingCycleService::class)
            ->nextBillingDateAfter('2026-09-15', '2026-08-20');

        $this->assertSame('2026-09-15', $date->toDateString());
    }
}
