<?php

use WHMCS\Database\Capsule;
use WHMCS\Service\Status;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/Api.php';

function keyhelp_MetaData()
{
    return [
        'DisplayName' => 'KeyHelp',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
        'ServiceSingleSignOnLabel' => 'Login to Control Panel',
        'AdminSingleSignOnLabel' => 'Login to Control Panel',
        'ListAccountsUniqueIdentifierDisplayName' => 'Username',
        'ListAccountsUniqueIdentifierField' => 'username',
        'ListAccountsProductField' => 'configoption1',
        'CustomFields' => true,
    ];
}

function keyhelp_ConfigOptions()
{
    return [
        'hostingplan' => [
            'FriendlyName' => 'Hosting Plan',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'KeyHelp Hosting Plan ID or Name',
            'Loader' => 'keyhelp_HostingPlanLoader',
            'SimpleMode' => true,
        ],
        'language' => [
            'FriendlyName' => 'Default Language',
            'Type' => 'dropdown',
            'Options' => 'en,de,es,fr,it,nl,pl,pt,ru',
            'Default' => 'en',
            'Description' => 'Default panel language',
            'SimpleMode' => true,
        ],
        'create_system_domain' => [
            'FriendlyName' => 'Create System Domain',
            'Type' => 'yesno',
            'Description' => 'Automatically create system domain',
            'Default' => 'on',
        ],
        'send_credentials' => [
            'FriendlyName' => 'Send Login Credentials',
            'Type' => 'yesno',
            'Description' => 'Email login credentials to client',
            'Default' => 'on',
        ],
    ];
}

function keyhelp_HostingPlanLoader($params)
{
    try {
        $api = new Api($params);
        $plans = $api->getHostingPlans();
        
        $options = [];
        foreach ($plans as $plan) {
            $options[$plan['id']] = $plan['name'];
        }
        
        return $options;
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unable to load hosting plans: ' . $e->getMessage()];
    }
}

function keyhelp_TestConnection(array $params)
{
    try {
        $api = new Api($params);
        $result = $api->ping();
        
        if ($result) {
            $serverInfo = $api->getServerInfo();
            return [
                'success' => true,
                'error' => 'Connection successful! KeyHelp Version: ' . ($serverInfo['version'] ?? 'Unknown'),
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Connection failed: Unable to ping server',
        ];
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

function keyhelp_CreateAccount(array $params)
{
    try {
        $api = new Api($params);
        
        $username = keyhelp_GenerateUsername($params);
        $password = $params['password'];
        $email = $params['clientsdetails']['email'];
        $domain = $params['domain'];
        
        $clientData = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'language' => $params['configoption2'] ?: 'en',
            'id_hosting_plan' => $params['configoption1'],
            'create_system_domain' => $params['configoption3'] === 'on',
            'send_login_credentials' => $params['configoption4'] === 'on',
            'contact_data' => [
                'first_name' => $params['clientsdetails']['firstname'],
                'last_name' => $params['clientsdetails']['lastname'],
                'company' => $params['clientsdetails']['companyname'],
                'email' => $email,
                'telephone' => $params['clientsdetails']['phonenumber'],
                'address' => $params['clientsdetails']['address1'],
                'city' => $params['clientsdetails']['city'],
                'zip' => $params['clientsdetails']['postcode'],
                'state' => $params['clientsdetails']['state'],
                'country' => $params['clientsdetails']['countrycode'],
                'client_id' => $params['clientsdetails']['id'],
            ],
        ];
        
        if (function_exists('keyhelpModifyClientData')) {
            $clientData = keyhelpModifyClientData($clientData, $params);
        }
        
        $result = $api->createClient($clientData);
        
        Capsule::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update([
                'username' => $username,
                'password' => encrypt($password),
            ]);
        
        if ($domain && !$params['configoption3']) {
            try {
                $domainData = [
                    'id_user' => $result['id'],
                    'domain' => $domain,
                    'target' => ['target' => '/'],
                    'security' => [
                        'lets_encrypt' => true,
                        'is_prefer_https' => true,
                    ],
                ];
                
                if (function_exists('keyhelpModifyDomainData')) {
                    $domainData = keyhelpModifyDomainData($domainData, $params);
                }
                
                $api->createDomain($domainData);
            } catch (Exception $e) {
                logModuleCall('keyhelp', 'CreateDomain', $domainData, $e->getMessage(), $e->getTraceAsString());
            }
        }
        
        return 'success';
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function keyhelp_SuspendAccount(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        
        $client = $api->getClientByUsername($username);
        $api->updateClient($client['id'], ['is_suspended' => true]);
        
        return 'success';
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function keyhelp_UnsuspendAccount(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        
        $client = $api->getClientByUsername($username);
        $api->updateClient($client['id'], ['is_suspended' => false]);
        
        return 'success';
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function keyhelp_TerminateAccount(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        
        $client = $api->getClientByUsername($username);
        $api->deleteClient($client['id']);
        
        return 'success';
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function keyhelp_ChangePassword(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        $newPassword = $params['password'];
        
        $client = $api->getClientByUsername($username);
        $api->updateClient($client['id'], ['password' => $newPassword]);
        
        return 'success';
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function keyhelp_ChangePackage(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        $newPlanId = $params['configoption1'];
        
        $client = $api->getClientByUsername($username);
        $api->updateClient($client['id'], ['id_hosting_plan' => $newPlanId]);
        
        return 'success';
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function keyhelp_ServiceSingleSignOn(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        
        $client = $api->getClientByUsername($username);
        $loginUrl = $api->generateLoginUrl($client['id']);
        
        return [
            'success' => true,
            'redirectTo' => $loginUrl['url'],
        ];
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return [
            'success' => false,
            'errorMsg' => $e->getMessage(),
        ];
    }
}

function keyhelp_Details(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        
        $client = $api->getClientByUsername($username);
        $resources = $api->getClientResources($client['id']);
        $stats = $api->getClientStats($client['id']);
        
        return [
            'templatefile' => 'templates/details',
            'vars' => [
                'username' => $username,
                'password' => $params['password'],
                'client' => $client,
                'resources' => $resources,
                'stats' => $stats,
                'serviceid' => $params['serviceid'],
            ],
        ];
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return [
            'templatefile' => 'templates/error',
            'vars' => [
                'error' => $e->getMessage(),
            ],
        ];
    }
}

function keyhelp_AdminServicesTabFields(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        
        $client = $api->getClientByUsername($username);
        $stats = $api->getClientStats($client['id']);
        
        $diskUsed = round($stats['disk_space']['value'] / 1024 / 1024 / 1024, 2);
        $diskMax = $stats['disk_space']['max'] == -1 ? 'Unlimited' : round($stats['disk_space']['max'] / 1024 / 1024 / 1024, 2) . ' GB';
        
        $fields = [
            'KeyHelp ID' => $client['id'],
            'Status' => $client['is_suspended'] ? '<span class="label label-danger">Suspended</span>' : '<span class="label label-success">Active</span>',
            'Created' => $client['created_at'],
            'Disk Usage' => $diskUsed . ' GB / ' . $diskMax,
            'Domains' => $stats['domains']['value'] . ' / ' . ($stats['domains']['max'] == -1 ? 'Unlimited' : $stats['domains']['max']),
            'Email Accounts' => $stats['email_accounts']['value'] . ' / ' . ($stats['email_accounts']['max'] == -1 ? 'Unlimited' : $stats['email_accounts']['max']),
            'Databases' => $stats['databases']['value'] . ' / ' . ($stats['databases']['max'] == -1 ? 'Unlimited' : $stats['databases']['max']),
        ];
        
        return $fields;
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return ['Error' => $e->getMessage()];
    }
}

function keyhelp_AdminCustomButtonArray()
{
    return [
        'Sync Account' => 'sync_account',
    ];
}

function keyhelp_ClientAreaCustomButtonArray()
{
    return [
        'View Statistics' => 'Details',
    ];
}

function keyhelp_sync_account(array $params)
{
    try {
        $api = new Api($params);
        $username = $params['username'];
        
        $client = $api->getClientByUsername($username);
        $stats = $api->getClientStats($client['id']);
        $traffic = $api->getClientTraffic($client['id']);
        
        $totalTraffic = 0;
        foreach (['http', 'ftp', 'smtp', 'pop3', 'imap'] as $protocol) {
            if (isset($traffic[$protocol])) {
                $totalTraffic += $traffic[$protocol]['in'] + $traffic[$protocol]['out'];
            }
        }
        
        Capsule::table('tblhosting')
            ->where('id', $params['serviceid'])
            ->update([
                'diskusage' => round($stats['disk_space']['value'] / 1024 / 1024),
                'disklimit' => $stats['disk_space']['max'] == -1 ? 0 : round($stats['disk_space']['max'] / 1024 / 1024),
                'bwusage' => round($totalTraffic / 1024 / 1024),
                'bwlimit' => $stats['traffic']['max'] == -1 ? 0 : round($stats['traffic']['max'] / 1024 / 1024),
                'lastupdate' => Capsule::raw('now()'),
            ]);
        
        return [
            'success' => 'Account synced successfully!'
        ];
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return 'Error: ' . $e->getMessage();
    }
}

function keyhelp_UsageUpdate(array $params)
{
    try {
        $api = new Api($params);
        $serverId = $params['serverid'];
        
        $clients = $api->getAllClients();
        
        foreach ($clients as $client) {
            try {
                $stats = $api->getClientStats($client['id']);
                $traffic = $api->getClientTraffic($client['id']);
                
                $totalTraffic = 0;
                foreach (['http', 'ftp', 'smtp', 'pop3', 'imap'] as $protocol) {
                    if (isset($traffic[$protocol])) {
                        $totalTraffic += $traffic[$protocol]['in'] + $traffic[$protocol]['out'];
                    }
                }
                
                Capsule::table('tblhosting')
                    ->where('server', $serverId)
                    ->where('username', $client['username'])
                    ->update([
                        'diskusage' => round($stats['disk_space']['value'] / 1024 / 1024),
                        'disklimit' => $stats['disk_space']['max'] == -1 ? 0 : round($stats['disk_space']['max'] / 1024 / 1024),
                        'bwusage' => round($totalTraffic / 1024 / 1024),
                        'bwlimit' => $stats['traffic']['max'] == -1 ? 0 : round($stats['traffic']['max'] / 1024 / 1024),
                        'lastupdate' => Capsule::raw('now()'),
                    ]);
            } catch (Exception $e) {
                logModuleCall('keyhelp', 'UsageUpdate_Client', ['client' => $client['username']], $e->getMessage(), $e->getTraceAsString());
            }
        }
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
    }
}

function keyhelp_ListAccounts(array $params)
{
    try {
        $api = new Api($params);
        $clients = $api->getAllClients();
        
        $accounts = [];
        foreach ($clients as $client) {
            $primaryDomain = '';
            try {
                $resources = $api->getClientResources($client['id']);
                if (!empty($resources['domains'])) {
                    foreach ($resources['domains'] as $domain) {
                        if (!$domain['is_system_domain']) {
                            $primaryDomain = $domain['domain'];
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                logModuleCall('keyhelp', 'ListAccounts_GetDomain', ['client_id' => $client['id']], $e->getMessage());
            }
            
            if (empty($primaryDomain)) {
                $primaryDomain = $client['username'] . '.' . $params['serverhostname'];
            }
            
            $createdDate = '';
            if (!empty($client['created_at'])) {
                try {
                    $dateTime = new DateTime($client['created_at']);
                    $createdDate = $dateTime->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    logModuleCall('keyhelp', 'ListAccounts_DateConversion', ['date' => $client['created_at']], $e->getMessage());
                    $createdDate = date('Y-m-d H:i:s');
                }
            }
            
            $accounts[] = [
                'email' => $client['email'],
                'username' => $client['username'],
                'domain' => $primaryDomain,
                'uniqueIdentifier' => $client['username'],
                'product' => $client['id_hosting_plan'] ?? 'Unknown',
                'primaryip' => $params['serverip'] ?? '',
                'created' => $createdDate,
                'status' => $client['is_suspended'] ? Status::SUSPENDED : Status::ACTIVE,
            ];
        }
        
        return [
            'success' => true,
            'accounts' => $accounts,
        ];
    } catch (Exception $e) {
        logModuleCall('keyhelp', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

function keyhelp_GenerateUsername($params)
{
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        if (function_exists('keyhelpOverrideUsername')) {
            return keyhelpOverrideUsername($params);
        }
    }

    $letters = 'abcdefghijklmnopqrstuvwxyz';
    $username = '';
    $username .= $letters[random_int(0, 25)];
    $username .= random_int(10, 99);
    $username .= $letters[random_int(0, 25)];
    $username .= $letters[random_int(0, 25)];

    while (keyhelp_UsernameExists($username, $params)) {
        $username = '';
        $username .= $letters[random_int(0, 25)];
        $username .= random_int(10, 99);
        $username .= $letters[random_int(0, 25)];
        $username .= $letters[random_int(0, 25)];
    }

    return $username;
}

function keyhelp_UsernameExists($username, $params)
{
    $result = Capsule::table('tblhosting')
        ->where('server', $params['serverid'])
        ->where('username', $username)
        ->count();
    
    return $result > 0;
}