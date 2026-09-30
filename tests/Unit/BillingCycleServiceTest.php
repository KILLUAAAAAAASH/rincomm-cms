<?php

namespace Tests\Unit;

use App\Services\BillingCycleService;
use PHPUnit\Framework\TestCase;

class BillingCycleServiceTest extends TestCase
{
    private BillingCycleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BillingCycleService();
    }

    public function test_standard_monthly_cycle_keeps_anchor_day(): void
    {
        $cycleOne = $this->service->datesForCycle(
            '2026-09-23',
            1,
            1,
            5
        );

        $this->assertSame(
            '2026-09-23',
            $cycleOne['billing_date']->toDateString()
        );

        $this->assertSame(
            '2026-10-23',
            $cycleOne['due_date']->toDateString()
        );

        $this->assertSame(
            '2026-10-28',
            $cycleOne['disconnection_notice_date']->toDateString()
        );

        $cycleTwo = $this->service->datesForCycle(
            '2026-09-23',
            2,
            1,
            5
        );

        $this->assertSame(
            '2026-10-23',
            $cycleTwo['billing_date']->toDateString()
        );

        $this->assertSame(
            '2026-11-23',
            $cycleTwo['due_date']->toDateString()
        );
    }

    public function test_short_month_does_not_permanently_move_anchor(): void
    {
        $cycleOne = $this->service->datesForCycle(
            '2026-01-31',
            1,
            1,
            5
        );

        $this->assertSame(
            '2026-02-28',
            $cycleOne['due_date']->toDateString()
        );

        $cycleTwo = $this->service->datesForCycle(
            '2026-01-31',
            2,
            1,
            5
        );

        $this->assertSame(
            '2026-02-28',
            $cycleTwo['billing_date']->toDateString()
        );

        $this->assertSame(
            '2026-03-31',
            $cycleTwo['due_date']->toDateString()
        );
    }

    public function test_leap_year_uses_february_29(): void
    {
        $cycleOne = $this->service->datesForCycle(
            '2028-01-31',
            1,
            1,
            5
        );

        $this->assertSame(
            '2028-02-29',
            $cycleOne['due_date']->toDateString()
        );

        $cycleTwo = $this->service->datesForCycle(
            '2028-01-31',
            2,
            1,
            5
        );

        $this->assertSame(
            '2028-03-31',
            $cycleTwo['due_date']->toDateString()
        );
    }

    public function test_thirtieth_remains_thirtieth_when_available(): void
    {
        $cycle = $this->service->datesForCycle(
            '2026-04-30',
            1,
            1,
            5
        );

        $this->assertSame(
            '2026-05-30',
            $cycle['due_date']->toDateString()
        );
    }
}
