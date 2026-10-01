<?php

namespace App\Services;

use InvalidArgumentException;

class PhilippinePhoneNumberService
{
    /**
     * Normalize a Philippine mobile number to:
     *
     * 639XXXXXXXXX
     *
     * Accepted examples:
     * 09171234567
     * 0917 123 4567
     * 0917-123-4567
     * +639171234567
     * +63 917 123 4567
     * 639171234567
     * 9171234567
     */
    public function normalize(string $phoneNumber): string
    {
        $phoneNumber = trim($phoneNumber);

        if ($phoneNumber === '') {
            throw new InvalidArgumentException(
                'A mobile number is required.'
            );
        }

        /*
         * Allow common visual separators only.
         *
         * Reject other characters before normalization so letters and
         * unexpected punctuation cannot be silently stripped into something
         * that happens to resemble a valid mobile number.
         */
        if (
            ! preg_match(
                '/^\+?[0-9\s\-\(\)]+$/',
                $phoneNumber
            )
        ) {
            throw new InvalidArgumentException(
                'Please enter a valid Philippine mobile number.'
            );
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $phoneNumber
        );

        if (! is_string($digits)) {
            throw new InvalidArgumentException(
                'Please enter a valid Philippine mobile number.'
            );
        }

        /*
         * Convert supported Philippine representations to the national
         * mobile subscriber portion: 9XXXXXXXXX.
         */
        if (
            strlen($digits) === 11
            && str_starts_with($digits, '09')
        ) {
            $subscriberNumber = substr(
                $digits,
                1
            );
        } elseif (
            strlen($digits) === 12
            && str_starts_with($digits, '639')
        ) {
            $subscriberNumber = substr(
                $digits,
                2
            );
        } elseif (
            strlen($digits) === 10
            && str_starts_with($digits, '9')
        ) {
            $subscriberNumber = $digits;
        } else {
            throw new InvalidArgumentException(
                'Please enter a valid Philippine mobile number.'
            );
        }

        if (
            ! preg_match(
                '/^9\d{9}$/',
                $subscriberNumber
            )
        ) {
            throw new InvalidArgumentException(
                'Please enter a valid Philippine mobile number.'
            );
        }

        return '63' . $subscriberNumber;
    }

    /**
     * Convert a Philippine mobile number to the familiar domestic format:
     *
     * 0917 123 4567
     */
    public function display(string $phoneNumber): string
    {
        $normalized = $this->normalize(
            $phoneNumber
        );

        $subscriberNumber = substr(
            $normalized,
            2
        );

        return sprintf(
            '0%s %s %s',
            substr($subscriberNumber, 0, 3),
            substr($subscriberNumber, 3, 3),
            substr($subscriberNumber, 6, 4)
        );
    }

    /**
     * Mask a phone number for verification screens and operational messages.
     *
     * Example:
     * 639171234567 -> 09•• ••• 4567
     */
    public function mask(string $phoneNumber): string
    {
        $normalized = $this->normalize(
            $phoneNumber
        );

        return sprintf(
            '09•• ••• %s',
            substr($normalized, -4)
        );
    }
}
