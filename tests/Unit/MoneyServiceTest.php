<?php

namespace Tests\Unit;

use App\Services\MoneyService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyServiceTest extends TestCase
{
    private MoneyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MoneyService();
    }

    public function test_converts_decimal_amounts_to_integer_cents(): void
    {
        $this->assertSame(
            0,
            $this->service->toCents('0')
        );

        $this->assertSame(
            100,
            $this->service->toCents('1')
        );

        $this->assertSame(
            120,
            $this->service->toCents('1.2')
        );

        $this->assertSame(
            12345,
            $this->service->toCents('123.45')
        );
    }

    public function test_normalizes_null_and_whitespace_amounts(): void
    {
        $this->assertSame(
            0,
            $this->service->toCents(null)
        );

        $this->assertSame(
            164900,
            $this->service->toCents(' 1649.00 ')
        );
    }

    public function test_converts_integer_cents_to_decimal_string(): void
    {
        $this->assertSame(
            '0.00',
            $this->service->toDecimal(0)
        );

        $this->assertSame(
            '1.00',
            $this->service->toDecimal(100)
        );

        $this->assertSame(
            '1.20',
            $this->service->toDecimal(120)
        );

        $this->assertSame(
            '123.45',
            $this->service->toDecimal(12345)
        );
    }

    public function test_rejects_invalid_decimal_amounts(): void
    {
        foreach ([
            '',
            '-1.00',
            '1.234',
            '1,000.00',
            'abc',
            '.50',
        ] as $amount) {
            try {
                $this->service->toCents($amount);

                $this->fail(
                    sprintf(
                        'Expected invalid amount "%s" to be rejected.',
                        $amount
                    )
                );
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Invalid monetary amount.',
                    $exception->getMessage()
                );
            }
        }
    }

    public function test_rejects_negative_integer_cents(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Monetary amount cannot be negative.'
        );

        $this->service->toDecimal(-1);
    }
}
