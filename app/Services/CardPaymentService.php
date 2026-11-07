<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class CardPaymentService
{
    /**
     * Supported card brand regex patterns.
     */
    protected array $cardPatterns = [
        'PayPak'           => '/^(?:508126|5081|6007|6271|9999)[0-9]{10,12}$/',
        'Visa'             => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        'Mastercard'       => '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/',
        'American Express' => '/^3[47][0-9]{13}$/',
        'Discover'         => '/^(?:6011|65[0-9]{2}|64[4-9][0-9])[0-9]{12}$/',
        'JCB'              => '/^(?:2131|1800|35\d{3})\d{11}$/',
        'UnionPay'         => '/^(?:62[0-9]{14,17})$/',
        'Maestro'          => '/^(?:5018|5020|5038|5893|6304|6759|6761|6762|6763)[0-9]{8,15}$/',
    ];

    /**
     * Clean and sanitize card number by removing all whitespace and hyphens.
     */
    public function sanitizeCardNumber(string $cardNumber): string
    {
        return preg_replace('/\D/', '', $cardNumber);
    }

    /**
     * Validate card number using Luhn Algorithm (Mod 10 Checksum).
     */
    public function validateLuhn(string $cardNumber): bool
    {
        $number = $this->sanitizeCardNumber($cardNumber);
        $length = strlen($number);

        if ($length < 13 || $length > 19) {
            return false;
        }

        $sum = 0;
        $alternate = false;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = !$alternate;
        }

        return ($sum % 10 === 0);
    }

    /**
     * Detect Card Brand/Network (Visa, Mastercard, etc.)
     */
    public function detectCardBrand(string $cardNumber): string
    {
        $cleanNumber = $this->sanitizeCardNumber($cardNumber);

        foreach ($this->cardPatterns as $brand => $pattern) {
            if (preg_match($pattern, $cleanNumber)) {
                return $brand;
            }
        }

        return 'Bank Card';
    }

    /**
     * Validate Expiration Date (MM/YY or MM/YYYY) and ensure card is not expired.
     *
     * @return array{valid: bool, month: string, year: string, error?: string}
     */
    public function validateExpiry(string $expiry): array
    {
        $cleanExpiry = trim($expiry);

        // Accept format MM/YY or MM/YYYY
        if (!preg_match('/^(0[1-9]|1[0-2])\s*[\/\-]\s*([0-9]{2}|[0-9]{4})$/', $cleanExpiry, $matches)) {
            return [
                'valid' => false,
                'month' => '',
                'year'  => '',
                'error' => 'Invalid expiry date format. Please use MM/YY.',
            ];
        }

        $month = (int) $matches[1];
        $yearPart = $matches[2];
        $year = (strlen($yearPart) === 2) ? (int) ('20' . $yearPart) : (int) $yearPart;

        $now = Carbon::now();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        if ($year < $currentYear || ($year === $currentYear && $month < $currentMonth)) {
            return [
                'valid' => false,
                'month' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'year'  => (string) $year,
                'error' => 'Card has expired. Please use a valid non-expired card.',
            ];
        }

        if ($year > ($currentYear + 20)) {
            return [
                'valid' => false,
                'month' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'year'  => (string) $year,
                'error' => 'Expiry year is too far in the future.',
            ];
        }

        return [
            'valid' => true,
            'month' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'year'  => (string) $year,
        ];
    }

    /**
     * Validate CVV / CVC code.
     */
    public function validateCvv(string $cvv, string $brand = ''): bool
    {
        $cleanCvv = preg_replace('/\D/', '', $cvv);
        $len = strlen($cleanCvv);

        if ($brand === 'American Express') {
            return $len === 4;
        }

        return $len === 3 || $len === 4;
    }

    /**
     * Perform complete validation and prepare safe sanitized card metadata.
     * NEVER returns full card number or CVV (PCI-DSS compliance).
     *
     * @throws InvalidArgumentException
     */
    public function processCardDetails(array $cardInput): array
    {
        $holderName = trim($cardInput['card_holder_name'] ?? '');
        $rawNumber  = $cardInput['card_number'] ?? '';
        $rawExpiry  = $cardInput['card_expiry'] ?? '';
        $rawCvv     = $cardInput['card_cvv'] ?? '';

        if (empty($holderName) || strlen($holderName) < 3) {
            throw new InvalidArgumentException('Please provide a valid Cardholder Name.');
        }

        $cleanNumber = $this->sanitizeCardNumber($rawNumber);
        if (!$this->validateLuhn($cleanNumber)) {
            throw new InvalidArgumentException('Invalid card number. Please check the digits and try again.');
        }

        $brand = $this->detectCardBrand($cleanNumber);

        $expiryCheck = $this->validateExpiry($rawExpiry);
        if (!$expiryCheck['valid']) {
            throw new InvalidArgumentException($expiryCheck['error'] ?? 'Invalid card expiry.');
        }

        if (!$this->validateCvv($rawCvv, $brand)) {
            throw new InvalidArgumentException('Invalid CVV/CVC code. Please check the 3 or 4 digits on the back of your card.');
        }

        // Return ONLY safe sanitized information
        return [
            'card_holder_name' => $holderName,
            'card_brand'       => $brand,
            'card_last_four'   => substr($cleanNumber, -4),
            'card_exp_month'   => $expiryCheck['month'],
            'card_exp_year'    => $expiryCheck['year'],
        ];
    }
}
