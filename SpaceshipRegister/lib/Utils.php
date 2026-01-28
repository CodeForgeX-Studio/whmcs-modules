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

namespace Spaceship;

class Utils
{
    private static string $logFile = __DIR__ . '/../logs/api.log';
    public static bool $IsDebugMode = false;
    
    /**
     * Logs messages with different levels (INFO, ERROR, DEBUG)
     * 
     * @param string $message The log message
     * @param string $level The log level (INFO, ERROR, DEBUG)
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        try {
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logEntry = json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => strtoupper($level),
                'message' => $message
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            file_put_contents(self::$logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            error_log('Logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Formats phone number to Spaceship API format (+X.XXXXXXXX)
     * 
     * @param string $phone The phone number to format
     * @return string Formatted phone number
     */
    public static function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        if (strpos($phone, '+') !== 0) {
            $phone = '+' . $phone;
        }
        
        if (preg_match('/^\+(\d{1,3})(\d+)$/', $phone, $matches)) {
            return '+' . $matches[1] . '.' . $matches[2];
        }
        
        return $phone;
    }
    
    /**
     * Validates contact data before sending to API
     * 
     * @param array $contactData Contact data to validate
     * @return array Validated and formatted contact data
     * @throws \Exception If validation fails
     */
    public static function validateContactData(array $contactData): array
    {
        $required = ['firstName', 'lastName', 'email', 'address1', 'city', 'country', 'phone'];
        
        foreach ($required as $field) {
            if (empty($contactData[$field])) {
                throw new \Exception("Missing required field: $field");
            }
        }
        
        if (!empty($contactData['phone'])) {
            $contactData['phone'] = self::formatPhoneNumber($contactData['phone']);
        }
        if (!empty($contactData['fax'])) {
            $contactData['fax'] = self::formatPhoneNumber($contactData['fax']);
        }
        
        if (!empty($contactData['country'])) {
            $contactData['country'] = strtoupper($contactData['country']);
        }
        
        return $contactData;
    }
    
    /**
     * Converts WHMCS domain status to Spaceship lifecycle status
     * 
     * @param string $whmcsStatus WHMCS domain status
     * @return string Spaceship lifecycle status
     */
    public static function convertDomainStatus(string $whmcsStatus): string
    {
        $statusMap = [
            'Active' => 'registered',
            'Expired' => 'expired',
            'Cancelled' => 'deleted',
            'Pending' => 'pending',
            'Pending Transfer' => 'pending',
            'Redemption' => 'redemption',
        ];
        
        return $statusMap[$whmcsStatus] ?? 'registered';
    }
    
    /**
     * Sanitizes domain name for API calls
     * 
     * @param string $domain Domain name to sanitize
     * @return string Sanitized domain name
     */
    public static function sanitizeDomain(string $domain): string
    {
        return strtolower(trim($domain));
    }
}