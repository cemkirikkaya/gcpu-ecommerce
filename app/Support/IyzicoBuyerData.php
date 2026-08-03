<?php

namespace App\Support;

use RuntimeException;

class IyzicoBuyerData
{
    public static function email(string $email, int $userId): string
    {
        $email = trim(mb_strtolower($email));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Geçerli bir e-posta adresi gerekli.');
        }

        if (preg_match('/\.(test|invalid|localhost)$/i', $email) === 1) {
            return 'buyer'.$userId.'@example.com';
        }

        return $email;
    }

    public static function gsm(?string $phone): string
    {
        if ($phone === null || trim($phone) === '') {
            return '+905555555555';
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+9'.$digits;
        }

        if (strlen($digits) === 10) {
            return '+90'.$digits;
        }

        return '+905555555555';
    }
}
