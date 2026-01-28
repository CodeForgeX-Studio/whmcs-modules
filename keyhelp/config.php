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