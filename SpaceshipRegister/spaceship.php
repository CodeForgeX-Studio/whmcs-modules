<?php

/*
 * Author: CodeForgeX Studio
 * GitHub: https://github.com/CodeForgeX-Studio
 * Website: https://codeforgex.studio
 * 
 * This file is part of the Spaceship Registrar Module for WHMCS.
 * All rights reserved.
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;
use Spaceship\SpaceshipAPI;
use Spaceship\Utils;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Carbon;

require_once __DIR__ . '/lib/SpaceshipAPI.php';
require_once __DIR__ . '/lib/Utils.php';

function validateApiCredentials($params) {
    if (empty($params['APIKey']) || empty($params['APISecret']) || empty($params['APIEndPoint'])) {
        throw new Exception("API credentials are missing.");
    }
}

function getDomainName($params) {
    return $params['domainname'] ?? $params['sld'] . '.' . $params['tld'];
}

function handleApiResponse($response) {
    
    if (Utils::$IsDebugMode) {
        Utils::log("Debug: API Response - " . json_encode($response));
    }
    
    if (isset($response['statusCode']) && $response['statusCode'] !== 200) {
        $message = json_decode($response['message'] ?? '{}', true);
        
        return ['error' => $message['detail'] ?? 'Unknown error'];
    }
    return null;
}

function getNameserversArray($params) {
    return array_values(array_filter([
        $params['ns1'] ?? null,
        $params['ns2'] ?? null,
        $params['ns3'] ?? null,
        $params['ns4'] ?? null,
        $params['ns5'] ?? null
    ]));
}

function initApi($params) {
    validateApiCredentials($params);
    
    Utils::$IsDebugMode = $params['DebugMode'] ?? false;
    
    if (!empty($params['DebugMode'])) {
        Utils::log("Debug: Initializing SpaceshipAPI with endpoint: {$params['APIEndPoint']}");
    }

    return new SpaceshipAPI($params['APIKey'], $params['APISecret'], $params['APIEndPoint']);
}

function createContactFromParams($api, $params) {
    try {
        $phone = $params['phonenumber'] ?? '';
        $phonecc = $params['phonecc'] ?? '1';
        $fullPhone = '+' . ltrim($phonecc, '+') . '.' . preg_replace('/[^0-9]/', '', $phone);
        
        $contactData = [
            'firstName' => $params['firstname'] ?? 'Unknown',
            'lastName' => $params['lastname'] ?? 'Unknown',
            'email' => $params['email'] ?? '',
            'address1' => $params['address1'] ?? '',
            'city' => $params['city'] ?? '',
            'country' => strtoupper($params['countrycode'] ?? $params['country'] ?? 'US'),
            'phone' => $fullPhone
        ];
        
        // Optional fields
        if (!empty($params['companyname'])) {
            $contactData['organization'] = $params['companyname'];
        }
        if (!empty($params['address2'])) {
            $contactData['address2'] = $params['address2'];
        }
        if (!empty($params['state'])) {
            $contactData['stateProvince'] = $params['state'];
        }
        if (!empty($params['postcode'])) {
            $contactData['postalCode'] = $params['postcode'];
        }
        
        if (Utils::$IsDebugMode) {
            Utils::log("Debug: Creating contact with data: " . json_encode($contactData));
        }
        
        $response = $api->saveContactDetails($contactData);
        
        if ($error = handleApiResponse($response)) {
            throw new Exception("Failed to create contact: " . $error['error']);
        }
        
        if (empty($response['contactId'])) {
            throw new Exception("No contact ID returned from API");
        }
        
        return $response['contactId'];
        
    } catch (Exception $e) {
        Utils::log("Error creating contact: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

function spaceship_MetaData() {
    return [
        'DisplayName' => 'Spaceship',
        'APIVersion' => '1.0.0',
    ];
}

function spaceship_getConfigArray() {
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Spaceship',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Domain registrar module for Spaceship.com - Automatically creates contacts from WHMCS client data',
        ],
        'APIKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your Spaceship API Key',
            'Default' => '',
        ],
        'APISecret' => [
            'FriendlyName' => 'API Secret',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Enter your Spaceship API Secret',
            'Default' => '',
        ],
        'APIEndPoint' => [
            'FriendlyName' => 'API Endpoint',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'API endpoint URL',
            'Default' => 'https://spaceship.dev/api/v1',
        ],
        'TestMode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Enable test mode (sandbox)',
            'Default' => 'no',
        ],
        'DebugMode' => [
            'FriendlyName' => 'Debug Mode',
            'Type' => 'yesno',
            'Description' => 'Enable debug logging',
            'Default' => 'no',
        ],
    ];
}

function spaceship_GetNameservers($params) {
    try {
        $api = initApi($params);
        $response = $api->getDomainInfo(getDomainName($params));
        
        if ($error = handleApiResponse($response)) return $error;
        
        $nameservers = [];
        if (!empty($response['nameservers']['hosts'])) {
            foreach ($response['nameservers']['hosts'] as $index => $nameserver) {
                $nameservers["ns" . ($index + 1)] = $nameserver;
            }
        }
        return $nameservers;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_SaveNameservers($params) {
    try {
        $api = initApi($params);
        $domain = getDomainName($params);
        
        $nameservers = getNameserversArray($params);
        if (empty($nameservers)) {
            return ['error' => 'No nameservers provided'];
        }
        
        $payload = [
            "provider" => "custom",
            "hosts" => $nameservers
        ];

        $response = $api->updateNameservers($domain, $payload);
        if ($error = handleApiResponse($response)) return $error;
        
        $result = [];
        if (!empty($response['hosts'])) {
            foreach ($response['hosts'] as $index => $nameserver) {
                $result["ns" . ($index + 1)] = $nameserver;
            }
        }
        return $result;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_GetRegistrarLock($params) {
    try {
        $api = initApi($params);
        $response = $api->getDomainInfo(getDomainName($params));
        
        if ($error = handleApiResponse($response)) return $error;
        
        $isLocked = isset($response['eppStatuses']) && 
                   in_array('clientTransferProhibited', $response['eppStatuses']);
        
        return $isLocked ? 'locked' : 'unlocked';
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_SaveRegistrarLock($params) {
    try {
        $api = initApi($params);
        $domain = getDomainName($params);
        
        $response = $api->getDomainInfo($domain);
        if ($error = handleApiResponse($response)) return $error;
        
        $isCurrentlyLocked = isset($response['eppStatuses']) && 
                            in_array('clientTransferProhibited', $response['eppStatuses']);
        
        $response = $api->updateTransferLock($domain, ["isLocked" => !$isCurrentlyLocked]);
        if ($error = handleApiResponse($response)) return $error;
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_RegisterDomain($params) {
    try {
        $api = initApi($params);
        $domainName = getDomainName($params);
        
        if (Utils::$IsDebugMode) {
            Utils::log("Debug: Starting domain registration for: " . $domainName);
        }
        
        $contactId = createContactFromParams($api, $params);
        
        if (Utils::$IsDebugMode) {
            Utils::log("Debug: Created contact ID: " . $contactId);
        }
        
        $payload = [
            'autoRenew' => false,
            'years' => (int)($params['regperiod'] ?? 1),
            'privacyProtection' => [
                'level' => !empty($params['idprotection']) ? 'high' : 'public',
                'userConsent' => true
            ],
            'contacts' => [
                'registrant' => $contactId,
                'admin' => $contactId,
                'tech' => $contactId,
                'billing' => $contactId,
                'attributes' => []
            ]
        ];
        
        if (Utils::$IsDebugMode) {
            Utils::log("Debug: Registering domain with payload: " . json_encode($payload));
        }
        
        $response = $api->registerDomain($domainName, $payload);
        if ($error = handleApiResponse($response)) return $error;

        $nameservers = getNameserversArray($params);
        if (!empty($nameservers) && count($nameservers) >= 2) {
            if (Utils::$IsDebugMode) {
                Utils::log("Debug: Setting nameservers: " . json_encode($nameservers));
            }
            
            $nsPayload = [
                'provider' => 'custom',
                'hosts' => $nameservers
            ];
            $api->updateNameservers($domainName, $nsPayload);
        }

        return ['success' => true];
    } catch (Exception $e) {
        Utils::log("Error registering domain: " . $e->getMessage(), "ERROR");
        return ['error' => $e->getMessage()];
    }
}

function spaceship_TransferDomain($params) {
    try {
        $api = initApi($params);
        $domainName = getDomainName($params);
        
        $contactId = createContactFromParams($api, $params);
        
        $payload = [
            'autoRenew' => false,
            'privacyProtection' => [
                'level' => !empty($params['idprotection']) ? 'high' : 'public',
                'userConsent' => true
            ],
            'contacts' => [
                'registrant' => $contactId,
                'admin' => $contactId,
                'tech' => $contactId,
                'billing' => $contactId,
                'attributes' => []
            ],
            'authCode' => $params['eppcode'] ?? ''
        ];
        
        $response = $api->transferDomain($domainName, $payload);
        
        if ($error = handleApiResponse($response)) return $error;
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_RenewDomain($params) {
    try {
        $api = initApi($params);
        $domainName = getDomainName($params);
        
        $domainInfo = $api->getDomainInfo($domainName);
        if ($error = handleApiResponse($domainInfo)) return $error;
        
        $payload = [
            'years' => (int)($params['regperiod'] ?? 1),
            'currentExpirationDate' => $domainInfo['expirationDate']
        ];
        
        $response = $api->renewDomain($domainName, $payload);
        
        if ($error = handleApiResponse($response)) return $error;
        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_GetEPPCode($params) {
    try {
        $api = initApi($params);
        $response = $api->getAuthCode(getDomainName($params));
        if ($error = handleApiResponse($response)) return $error;
        return ["eppcode" => $response['authCode']];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_Sync($params) {
    try {
        $api = initApi($params);
        $response = $api->getDomainInfo(getDomainName($params));
        
        if ($error = handleApiResponse($response)) return $error;

        $expirationDate = strtotime($response['expirationDate']);
        if (!$expirationDate) {
            throw new Exception("Invalid expiration date format.");
        }

        return [
            'expirydate' => date('Y-m-d', $expirationDate),
            'active' => true,
            'expired' => false,
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_GetDomainInformation($params) {
    try {
        $api = initApi($params);
        $response = $api->getDomainInfo(getDomainName($params));
        
        if ($error = handleApiResponse($response)) {
            throw new Exception($error['error']);
        }

        $nameservers = [];
        if (!empty($response['nameservers']['hosts'])) {
            foreach ($response['nameservers']['hosts'] as $index => $nameserver) {
                $nameservers["ns" . ($index + 1)] = $nameserver;
            }
        }

        $isLocked = isset($response['eppStatuses']) && 
                   in_array('clientTransferProhibited', $response['eppStatuses']);

        $expirationDate = null;
        if (!empty($response['expirationDate'])) {
            try {
                $expirationDate = Carbon::parse($response['expirationDate']);
            } catch (Exception $e) {
                Utils::log("Invalid expiration date format: {$response['expirationDate']}", "error");
            }
        }

        return (new Domain())
            ->setDomain($response['name'] ?? '')
            ->setNameservers($nameservers)
            ->setRegistrationStatus($response['verificationStatus'] ?? 'unknown')
            ->setTransferLock($isLocked)
            ->setExpiryDate($expirationDate)
            ->setRestorable($response['lifecycleStatus'] === 'redemption')
            ->setIdProtectionStatus(($response['privacyProtection']['level'] ?? '') === 'high')
            ->setDnsManagementStatus(($response['nameservers']['provider'] ?? '') === 'basic')
            ->setEmailForwardingStatus($response['privacyProtection']['contactForm'] ?? false);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_RequestDelete($params) {
    return ['error' => 'Domain deletion must be done directly in Spaceship account'];
}

function spaceship_GetTldPricing(array $params) {
    return new ResultsList();
}