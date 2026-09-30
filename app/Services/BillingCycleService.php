<?php

namespace App\Services;

use App\Models\BillingSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class BillingCycleService
{
    public function datesForCycle(
        CarbonInterface|string $anchorDate,
        int $cycleNumber,
        ?int $billingCycleMonths = null,
        ?int $disconnectionNoticeDays = null
    ): array {
        if ($cycleNumber < 1) {
            throw new InvalidArgumentException(
                'Cycle number must be at least 1.'
            );
        }

        [
            'billing_cycle_months' => $cycleMonths,
            'disconnection_notice_days' => $noticeDays,
        ] = $this->resolvePolicy(
            $billingCycleMonths,
            $disconnectionNoticeDays
        );

        $billingMonthOffset =
            ($cycleNumber - 1) * $cycleMonths;

        $dueMonthOffset =
            $cycleNumber * $cycleMonths;

        $billingDate = $this->anchoredDate(
            $anchorDate,
            $billingMonthOffset
        );

        $dueDate = $this->anchoredDate(
            $anchorDate,
            $dueMonthOffset
        );

        return [
            'billing_date' => $billingDate,
            'due_date' => $dueDate,
            'disconnection_notice_date' =>
                $dueDate->addDays($noticeDays),
        ];
    }

    private function anchoredDate(
        CarbonInterface|string $anchorDate,
        int $monthOffset
    ): CarbonImmutable {
        if ($monthOffset < 0) {
            throw new InvalidArgumentException(
                'Month offset cannot be negative.'
            );
        }

        $anchor = CarbonImmutable::parse($anchorDate)
            ->startOfDay();

        $targetMonth = $anchor
            ->startOfMonth()
            ->addMonthsNoOverflow($monthOffset);

        $targetDay = min(
            $anchor->day,
            $targetMonth->daysInMonth
        );

        return $targetMonth->setDate(
            $targetMonth->year,
            $targetMonth->month,
            $targetDay
        );
    }

    private function resolvePolicy(
        ?int $billingCycleMonths,
        ?int $disconnectionNoticeDays
    ): array {
        if (
            $billingCycleMonths === null ||
            $disconnectionNoticeDays === null
        ) {
            $settings = BillingSetting::query()
                ->where('setting_key', 'default')
                ->first();

            $billingCycleMonths ??=
                (int) ($settings?->billing_cycle_months ?? 1);

            $disconnectionNoticeDays ??=
                (int) ($settings?->disconnection_notice_days ?? 5);
        }

        if ($billingCycleMonths < 1) {
            throw new InvalidArgumentException(
                'Billing cycle must be at least 1 month.'
            );
        }

        if ($disconnectionNoticeDays < 0) {
            throw new InvalidArgumentException(
                'Disconnection notice days cannot be negative.'
            );
        }

        return [
            'billing_cycle_months' => $billingCycleMonths,
            'disconnection_notice_days' =>
                $disconnectionNoticeDays,
        ];
    }
}
