<?php

namespace App\Services;

class InvoiceBalanceService
{
    public function __construct(
        private readonly MoneyService $moneyService
    ) {}

    /**
     * @param iterable<string|int|float|null> $completedPaymentAmounts
     *
     * @return array{
     *     total_cents: int,
     *     paid_cents: int,
     *     balance_cents: int,
     *     total_amount: string,
     *     paid_amount: string,
     *     balance_amount: string
     * }
     */
    public function calculate(
        string|int|float|null $totalAmount,
        iterable $completedPaymentAmounts
    ): array {
        $totalCents = $this->moneyService->toCents(
            $totalAmount
        );

        $paidCents = 0;

        foreach ($completedPaymentAmounts as $amount) {
            $paidCents += $this->moneyService->toCents(
                $amount
            );
        }

        $balanceCents = max(
            0,
            $totalCents - $paidCents
        );

        return [
            'total_cents' => $totalCents,
            'paid_cents' => $paidCents,
            'balance_cents' => $balanceCents,
            'total_amount' => $this->moneyService->toDecimal(
                $totalCents
            ),
            'paid_amount' => $this->moneyService->toDecimal(
                $paidCents
            ),
            'balance_amount' => $this->moneyService->toDecimal(
                $balanceCents
            ),
        ];
    }
}
