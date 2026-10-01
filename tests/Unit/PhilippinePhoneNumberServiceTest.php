<?php

namespace Tests\Unit;

use App\Services\PhilippinePhoneNumberService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PhilippinePhoneNumberServiceTest extends TestCase
{
    private PhilippinePhoneNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PhilippinePhoneNumberService();
    }

    public function test_it_normalizes_supported_philippine_mobile_formats(): void
    {
        $numbers = [
            '09171234567',
            '0917 123 4567',
            '0917-123-4567',
            '(0917) 123 4567',
            '+639171234567',
            '+63 917 123 4567',
            '+63-917-123-4567',
            '639171234567',
            '9171234567',
        ];

        foreach ($numbers as $number) {
            $this->assertSame(
                '639171234567',
                $this->service->normalize($number),
                "Failed to normalize: {$number}"
            );
        }
    }

    public function test_it_trims_outer_whitespace_before_normalizing(): void
    {
        $this->assertSame(
            '639171234567',
            $this->service->normalize('   09171234567   ')
        );
    }

    public function test_it_rejects_an_empty_mobile_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A mobile number is required.'
        );

        $this->service->normalize('   ');
    }

    public function test_it_rejects_mobile_numbers_containing_letters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Please enter a valid Philippine mobile number.'
        );

        $this->service->normalize('0917ABC4567');
    }

    public function test_it_rejects_unexpected_punctuation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Please enter a valid Philippine mobile number.'
        );

        $this->service->normalize('0917/123/4567');
    }

    public function test_it_rejects_a_non_philippine_country_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Please enter a valid Philippine mobile number.'
        );

        $this->service->normalize('+1 917 123 4567');
    }

    public function test_it_rejects_a_non_mobile_philippine_prefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Please enter a valid Philippine mobile number.'
        );

        $this->service->normalize('08171234567');
    }

    public function test_it_rejects_a_number_that_is_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Please enter a valid Philippine mobile number.'
        );

        $this->service->normalize('0917123456');
    }

    public function test_it_rejects_a_number_that_is_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Please enter a valid Philippine mobile number.'
        );

        $this->service->normalize('091712345678');
    }

    public function test_it_formats_a_mobile_number_for_display(): void
    {
        $this->assertSame(
            '0917 123 4567',
            $this->service->display('639171234567')
        );
    }

    public function test_display_accepts_noncanonical_input(): void
    {
        $this->assertSame(
            '0917 123 4567',
            $this->service->display('+63 917 123 4567')
        );
    }

    public function test_it_masks_a_mobile_number_for_verification_screens(): void
    {
        $this->assertSame(
            '09•• ••• 4567',
            $this->service->mask('639171234567')
        );
    }

    public function test_mask_accepts_noncanonical_input(): void
    {
        $this->assertSame(
            '09•• ••• 4567',
            $this->service->mask('0917 123 4567')
        );
    }
}
