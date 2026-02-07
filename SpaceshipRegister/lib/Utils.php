<?php
/*
 * Author: CodeForgeX Studio
 * GitHub: https://github.com/CodeForgeX-Studio
 * Website: https://codeforgex.studio
 *
 * This file is part of the Spaceship Registrar Module for WHMCS.
 * All rights reserved.
 */
 
declare(strict_types=1);

class Utils
{
    public static bool $IsDebugMode = false;

    public static function log(string $message, string $level = 'INFO'): void
    {
        $logEntry = sprintf(
            '[%s] %s: %s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        error_log($logEntry);
    }

    public static function sanitizeDomain(string $domain): string
    {
        return strtolower(trim($domain, " \t\n\r\0\x0B./-"));
    }

    public static function validateContactData(array $contactData): array
    {
        $requiredFields = ['firstName', 'lastName', 'email', 'address1', 'city', 'country', 'phone'];
        $validated = [];

        foreach ($requiredFields as $field) {
            if (isset($contactData[$field]) && !empty(trim($contactData[$field]))) {
                $validated[$field] = trim($contactData[$field]);
            }
        }

        if (empty($validated['email']) || !filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid email is required');
        }

        if (empty($validated['phone']) || !preg_match('/^\+[1-9]\d{1,14}$/', $validated['phone'])) {
            $validated['phone'] = '+0000000000';
        }

        if (empty($validated['country']) || strlen($validated['country']) !== 2) {
            $validated['country'] = 'US';
        }

        return $validated;
    }
}