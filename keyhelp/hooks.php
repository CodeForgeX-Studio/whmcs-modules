<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/Api.php';

function hook_keyhelp_clientedit(array $params)
{
    try {
        $services = Capsule::table('tblhosting')
            ->join('tblservers', 'tblhosting.server', '=', 'tblservers.id')
            ->where('tblhosting.userid', $params['userid'])
            ->where('tblservers.type', 'keyhelp')
            ->where('tblhosting.domainstatus', 'Active')
            ->select('tblhosting.*', 'tblservers.*')
            ->get();
        
        foreach ($services as $service) {
            try {
                $serverParams = [
                    'serverhostname' => $service->hostname,
                    'serveraccesshash' => decrypt($service->accesshash),
                    'serverpassword' => decrypt($service->password),
                    'serversecure' => $service->secure,
                    'serverport' => $service->port,
                ];
                
                $api = new Api($serverParams);
                $client = $api->getClientByUsername($service->username);
                
                $updateData = [
                    'email' => $params['email'],
                    'contact_data' => [
                        'first_name' => $params['firstname'],
                        'last_name' => $params['lastname'],
                        'company' => $params['companyname'],
                        'email' => $params['email'],
                        'telephone' => $params['phonenumber'],
                        'address' => $params['address1'],
                        'city' => $params['city'],
                        'zip' => $params['postcode'],
                        'state' => $params['state'],
                        'country' => $params['country'],
                        'client_id' => $params['id'],
                    ],
                ];
                
                $api->updateClient($client['id'], $updateData);
                
                logActivity("KeyHelp client updated for service ID: {$service->id}");
            } catch (Exception $e) {
                logModuleCall('keyhelp', 'hook_clientedit', $service, $e->getMessage(), $e->getTraceAsString());
            }
        }
    } catch (Exception $e) {
        logModuleCall('keyhelp', 'hook_clientedit', $params, $e->getMessage(), $e->getTraceAsString());
    }
}

add_hook('ClientEdit', 1, 'hook_keyhelp_clientedit');

function hook_keyhelp_aftermodulecreate(array $params)
{
    if ($params['params']['servertype'] !== 'keyhelp') {
        return;
    }
    
    try {
        $api = new Api($params['params']);
        $username = $params['params']['username'];
        
        if (!empty($username)) {
            $client = $api->getClientByUsername($username);
            $stats = $api->getClientStats($client['id']);
            
            Capsule::table('tblhosting')
                ->where('id', $params['params']['serviceid'])
                ->update([
                    'diskusage' => round($stats['disk_space']['value'] / 1024 / 1024),
                    'disklimit' => $stats['disk_space']['max'] == -1 ? 0 : round($stats['disk_space']['max'] / 1024 / 1024),
                    'lastupdate' => Capsule::raw('now()'),
                ]);
            
            logActivity("KeyHelp resources synced for service ID: {$params['params']['serviceid']}");
        }
    } catch (Exception $e) {
        logModuleCall('keyhelp', 'hook_aftermodulecreate', $params, $e->getMessage(), $e->getTraceAsString());
    }
}

add_hook('AfterModuleCreate', 1, 'hook_keyhelp_aftermodulecreate');

add_hook('ClientAreaPrimarySidebar', 1, function ($primarySidebar)
{
    if (!is_null($primarySidebar->getChild('Service Details Actions'))) {
        try {
            $serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($serviceId > 0) {
                $service = Capsule::table('tblhosting')
                    ->join('tblservers', 'tblhosting.server', '=', 'tblservers.id')
                    ->where('tblhosting.id', $serviceId)
                    ->where('tblservers.type', 'keyhelp')
                    ->first();
                
                if ($service) {
                    $primarySidebar->getChild('Service Details Actions')
                        ->addChild('Login to Panel')
                        ->setLabel('Login to Panel')
                        ->setUri('clientarea.php?action=productdetails&id=' . $serviceId . '&dosinglesignon=1')
                        ->setIcon('fa-sign-in-alt')
                        ->setOrder(100);
                }
            }
        } catch (Exception $e) {
            logModuleCall('keyhelp', 'hook_sidebar', [], $e->getMessage(), $e->getTraceAsString());
        }
    }
});

add_hook('DailyCronJob', 1, function()
{
    try {
        $servers = Capsule::table('tblservers')
            ->where('type', 'keyhelp')
            ->where('active', 1)
            ->get();
        
        foreach ($servers as $server) {
            try {
                $params = [
                    'serverid' => $server->id,
                    'serverhostname' => $server->hostname,
                    'serveraccesshash' => decrypt($server->accesshash),
                    'serverpassword' => decrypt($server->password),
                    'serversecure' => $server->secure,
                    'serverport' => $server->port,
                ];
                
                keyhelp_UsageUpdate($params);
                
                logActivity("KeyHelp daily sync completed for server: {$server->name}");
            } catch (Exception $e) {
                logModuleCall('keyhelp', 'daily_sync', ['server' => $server->id], $e->getMessage(), $e->getTraceAsString());
            }
        }
    } catch (Exception $e) {
        logModuleCall('keyhelp', 'daily_sync', [], $e->getMessage(), $e->getTraceAsString());
    }
});

function hook_keyhelp_aftermodulecreate_email(array $params)
{
    if ($params['params']['servertype'] !== 'keyhelp') {
        return;
    }
    
    if ($params['params']['configoption4'] !== 'on') {
        return;
    }
    
    try {
        $serviceid = $params['params']['serviceid'];
        $clientid = $params['params']['userid'];
        
        $client = Capsule::table('tblclients')->where('id', $clientid)->first();
        
        if ($client) {
            $postData = [
                'messagename' => 'Hosting Account Welcome Email',
                'id' => $serviceid,
                'customvars' => base64_encode(serialize([
                    'service_username' => $params['params']['username'],
                    'service_password' => $params['params']['password'],
                ])),
            ];
            
            localAPI('SendEmail', $postData);
            
            logActivity("KeyHelp welcome email sent for service ID: {$serviceid}");
        }
    } catch (Exception $e) {
        logModuleCall('keyhelp', 'hook_welcome_email', $params, $e->getMessage(), $e->getTraceAsString());
    }
}

add_hook('AfterModuleCreate', 2, 'hook_keyhelp_aftermodulecreate_email');

function hook_keyhelp_password_change(array $params)
{
    if ($params['params']['servertype'] !== 'keyhelp') {
        return;
    }
    
    try {
        logActivity("KeyHelp password changed for service ID: {$params['params']['serviceid']} by admin");
    } catch (Exception $e) {
        logModuleCall('keyhelp', 'hook_password_change', $params, $e->getMessage(), $e->getTraceAsString());
    }
}

add_hook('AfterModuleChangePassword', 1, 'hook_keyhelp_password_change');

function hook_keyhelp_after_terminate(array $params)
{
    if ($params['params']['servertype'] !== 'keyhelp') {
        return;
    }
    
    try {
        logActivity("KeyHelp account terminated for service ID: {$params['params']['serviceid']}");
        
    } catch (Exception $e) {
        logModuleCall('keyhelp', 'hook_after_terminate', $params, $e->getMessage(), $e->getTraceAsString());
    }
}

add_hook('AfterModuleTerminate', 1, 'hook_keyhelp_after_terminate');