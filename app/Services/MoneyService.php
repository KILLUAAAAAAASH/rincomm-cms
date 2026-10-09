<?php

namespace App\Services;

use InvalidArgumentException;

class MoneyService
{
    public function toCents(
        string|int|float|null $amount
    ): int {
        $value = trim(
            (string) ($amount ?? '0')
        );

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $value
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid monetary amount.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode(
                '.',
                $value,
                2
            ),
            2,
            ''
        );

        $fraction = str_pad(
            $fraction,
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) substr(
                $fraction,
                0,
                2
            );
    }

    public function toDecimal(
        int $cents
    ): string {
        if ($cents < 0) {
            throw new InvalidArgumentException(
                'Monetary amount cannot be negative.'
            );
        }

        return sprintf(
            '%d.%02d',
            intdiv(
                $cents,
                100
            ),
            $cents % 100
        );
    }
}
