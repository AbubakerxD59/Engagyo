<?php

namespace App\Services;

use App\Models\User;

class UsernameService
{
    /**
     * Generate a unique 8-10 character alphanumeric username from email address.
     * Uses the local part (before @) and includes numbers (e.g. john.doe@example.com -> johndoe42).
     *
     * @param string $email
     * @return string
     */
    public static function generate(string $email): string
    {
        $local = explode('@', trim($email))[0] ?? '';
        $base = self::buildBaseFromEmail($local);

        return self::ensureUnique($base);
    }

    /**
     * Build 8-10 character base from email local part (alphanumeric) + 2 digits.
     */
    private static function buildBaseFromEmail(string $local): string
    {
        $local = strtolower(preg_replace('/[^a-z0-9]/', '', $local));

        if (strlen($local) === 0) {
            $local = 'user';
        }

        $totalLength = random_int(8, 10);
        $baseCount = $totalLength - 2;
        $digits = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);

        $base = substr($local . str_repeat('a', $baseCount), 0, $baseCount);

        return $base . $digits;
    }

    /**
     * Ensure username is unique by varying digits or suffix if needed.
     */
    private static function ensureUnique(string $base): string
    {
        $username = $base;
        $attempt = 0;
        $maxAttempts = 1000;

        while (User::where('username', $username)->exists() && $attempt < $maxAttempts) {
            if ($attempt < 100) {
                $username = substr($base, 0, -2) . str_pad((string) ($attempt % 100), 2, '0', STR_PAD_LEFT);
            } else {
                $username = self::randomAlphanumeric(random_int(8, 10));
                while (User::where('username', $username)->exists()) {
                    $username = self::randomAlphanumeric(random_int(8, 10));
                }
                return $username;
            }
            $attempt++;
        }

        if (User::where('username', $username)->exists()) {
            do {
                $username = self::randomAlphanumeric(random_int(8, 10));
            } while (User::where('username', $username)->exists());
        }

        return $username;
    }

    private static function randomAlphanumeric(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, 35)];
        }
        return $result;
    }
}
