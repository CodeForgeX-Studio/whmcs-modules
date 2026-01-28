<?php

use WHMCS\Database\Capsule;

// Show DKIM records in email configuration
$showDkimRecords = 1;

/**
 * Check if username exists in WHMCS database OR KeyHelp server
 * 
 * @param string $username Username to check
 * @param array $params WHMCS parameters
 * @return bool True if username exists
 */
function keyhelpUsernameExistsAnywhere($username, $params)
{
    // Check WHMCS database (fast check first)
    $existsInWhmcs = Capsule::table('tblhosting')
        ->where('server', $params['serverid'])
        ->where('username', $username)
        ->count() > 0;
    
    if ($existsInWhmcs) {
        return true;
    }
    
    // Optional: Check KeyHelp API (can be slow, uncomment if needed)
    /*
    try {
        $api = new Api($params);
        $api->getClientByUsername($username);
        return true;
    } catch (Exception $e) {
        return false;
    }
    */
    
    return false;
}

/**
 * Custom function to modify username generation before account creation
 * Uncomment and modify as needed
 */
/*
function keyhelpOverrideUsername($params)
{
    if (!class_exists('Api')) {
        require_once __DIR__ . '/lib/Api.php';
    }
    
    $firstName = strtolower($params['clientsdetails']['firstname']);
    $lastName = strtolower($params['clientsdetails']['lastname']);
    
    $baseUsername = preg_replace('/[^a-z0-9]/', '', $firstName);
    
    if (strlen($baseUsername) < 3) {
        $baseUsername = preg_replace('/[^a-z0-9]/', '', $firstName . $lastName);
    }
    
    $baseUsername = substr($baseUsername, 0, 8);
    $username = $baseUsername;
    $counter = 1;
    
    while (keyhelpUsernameExistsAnywhere($username, $params)) {
        $username = $baseUsername . $counter;
        $counter++;
        
        if ($counter > 999) {
            $username = $baseUsername . rand(1000, 9999);
            break;
        }
    }
    
    return $username;
}
*/

/**
 * Custom function to modify client data before account creation
 * Uncomment and modify as needed
 */
/*
function keyhelpModifyClientData($clientData, $params)
{
    // Example: Force specific language
    // $clientData['language'] = 'nl';
    
    // Example: Add custom fields
    // $clientData['custom_field'] = 'value';
    
    return $clientData;
}
*/

/**
 * Custom function to modify domain data before domain creation
 * Uncomment and modify as needed
 */
/*
function keyhelpModifyDomainData($domainData, $params)
{
    // Example: Always enable Let's Encrypt
    // $domainData['security']['lets_encrypt'] = true;
    
    // Example: Set custom PHP version
    // $domainData['php_version'] = '8.1';
    
    return $domainData;
}
*/

/**
 * Custom validation for account creation
 * Return an error message to prevent creation, or null to continue
 * Uncomment and modify as needed
 */
/*
function keyhelpValidateAccountCreation($params)
{
    // Example: Require specific domain format
    if (!preg_match('/\.nl$/', $params['domain'])) {
        return 'Domain must end with .nl';
    }
    
    // Example: Check minimum username length
    if (strlen($params['username']) < 5) {
        return 'Username must be at least 5 characters';
    }
    
    return null; // No errors, proceed with creation
}
*/