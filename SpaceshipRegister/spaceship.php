<?php
/*
 * Author: CodeForgeX Studio
 * GitHub: https://github.com/CodeForgeX-Studio
 * Website: https://codeforgex.studio
 *
 * This file is part of the Spaceship Registrar Module for WHMCS.
 * All rights reserved.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Carbon;

require_once __DIR__ . '/lib/SpaceshipAPI.php';
require_once __DIR__ . '/lib/Utils.php';

function validateApiCredentials(array $params): void
{
    if (empty($params['APIKey']) || empty($params['APISecret']) || empty($params['APIEndPoint'])) {
        throw new Exception('API credentials are missing.');
    }
}

function getDomainName(array $params): string
{
    if (!empty($params['domainname'])) {
        return $params['domainname'];
    }

    $sld = $params['sld'] ?? '';
    $tld = $params['tld'] ?? '';

    return trim($sld . '.' . $tld, '.');
}

function handleApiResponse(array $response): ?array
{
    if (Utils::$IsDebugMode) {
        Utils::log('Debug: API Response - ' . json_encode($response));
    }

    if (!isset($response['statusCode'])) {
        return ['error' => 'Unknown API response'];
    }

    $statusCode = (int)$response['statusCode'];

    if ($statusCode >= 200 && $statusCode < 300) {
        return null;
    }

    if ($statusCode === 202) {
        return null;
    }

    $detail = null;

    if (isset($response['detail']) && $response['detail'] !== '') {
        $detail = $response['detail'];
    } elseif (!empty($response['message'])) {
        $decoded = json_decode($response['message'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $detail = $decoded['detail'] ?? $response['message'];
        } else {
            $detail = $response['message'];
        }
    }

    if ($detail === null || $detail === '') {
        $detail = 'Unknown error';
    }

    if (!empty($response['headers']['spaceship-error-code'][0])) {
        $detail .= ' [Spaceship code: ' . $response['headers']['spaceship-error-code'][0] . ']';
    }

    return ['error' => (string)$detail];
}

function getNameserversArray(array $params): array
{
    return array_values(array_filter([
        $params['ns1'] ?? null,
        $params['ns2'] ?? null,
        $params['ns3'] ?? null,
        $params['ns4'] ?? null,
        $params['ns5'] ?? null,
    ]));
}

function initApi(array $params): Spaceship\SpaceshipAPI
{
    validateApiCredentials($params);

    Utils::$IsDebugMode = (bool)($params['DebugMode'] ?? false);

    if (Utils::$IsDebugMode) {
        Utils::log('Debug: Initializing SpaceshipAPI with endpoint: ' . $params['APIEndPoint']);
    }

    return new Spaceship\SpaceshipAPI(
        (string)$params['APIKey'],
        (string)$params['APISecret'],
        (string)$params['APIEndPoint']
    );
}

function buildContactDataFromParams(array $params): array
{
    $phone   = $params['phonenumber'] ?? '';
    $phonecc = $params['phonecc'] ?? '';
    $fullPhone = trim($phonecc . $phone);

    $contactData = [
        'firstName' => $params['firstname'] ?? '',
        'lastName'  => $params['lastname'] ?? '',
        'email'     => $params['email'] ?? '',
        'address1'  => $params['address1'] ?? '',
        'city'      => $params['city'] ?? '',
        'country'   => $params['countrycode'] ?? $params['country'] ?? '',
        'phone'     => $fullPhone,
    ];

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
    if (!empty($params['fax'])) {
        $contactData['fax'] = $params['fax'];
    }

    return $contactData;
}

function createContactFromParams(Spaceship\SpaceshipAPI $api, array $params): string
{
    try {
        $contactData = buildContactDataFromParams($params);
        $contactData = Utils::validateContactData($contactData);

        if (Utils::$IsDebugMode) {
            Utils::log('Debug: Creating contact with data: ' . json_encode($contactData));
        }

        $response = $api->saveContactDetails($contactData);

        if ($error = handleApiResponse($response)) {
            throw new Exception('Failed to create contact: ' . $error['error']);
        }

        if (empty($response['contactId'])) {
            throw new Exception('No contact ID returned from API');
        }

        return (string)$response['contactId'];
    } catch (Exception $e) {
        Utils::log('Error creating contact: ' . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

function spaceship_MetaData(): array
{
    return [
        'DisplayName' => 'Spaceship',
        'APIVersion'  => '1.0.0',
    ];
}

function spaceship_getConfigArray(): array
{
    return [
        'FriendlyName' => [
            'Type'  => 'System',
            'Value' => 'Spaceship',
        ],
        'Description' => [
            'Type'  => 'System',
            'Value' => 'Domain registrar module for Spaceship.com - Automatically creates contacts from WHMCS client data',
        ],
        'APIKey' => [
            'FriendlyName' => 'API Key',
            'Type'         => 'text',
            'Size'         => '50',
            'Description'  => 'Enter your Spaceship API Key',
            'Default'      => '',
        ],
        'APISecret' => [
            'FriendlyName' => 'API Secret',
            'Type'         => 'password',
            'Size'         => '50',
            'Description'  => 'Enter your Spaceship API Secret',
            'Default'      => '',
        ],
        'APIEndPoint' => [
            'FriendlyName' => 'API Endpoint',
            'Type'         => 'text',
            'Size'         => '50',
            'Description'  => 'API endpoint URL',
            'Default'      => 'https://spaceship.dev/api/v1',
        ],
        'DebugMode' => [
            'FriendlyName' => 'Debug Mode',
            'Type'         => 'yesno',
            'Description'  => 'Enable debug logging',
            'Default'      => 'no',
        ],
    ];
}

function spaceship_GetNameservers(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $response = $api->getDomainInfo($domain);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        $nameservers = [];

        if (!empty($response['nameservers']['hosts']) && is_array($response['nameservers']['hosts'])) {
            foreach ($response['nameservers']['hosts'] as $index => $ns) {
                $nameservers['ns' . ($index + 1)] = $ns;
            }
        }

        return $nameservers;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_SaveNameservers(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $hosts = getNameserversArray($params);
        if (count($hosts) < 2) {
            return ['error' => 'At least two nameservers are required'];
        }

        $payload = [
            'provider' => 'custom',
            'hosts'    => $hosts,
        ];

        $response = $api->updateNameservers($domain, $payload);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        $result       = [];
        $returnedHosts = $response['hosts'] ?? $hosts;
        if (is_array($returnedHosts)) {
            foreach ($returnedHosts as $index => $ns) {
                $result['ns' . ($index + 1)] = $ns;
            }
        }

        return $result;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_GetRegistrarLock(array $params)
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $response = $api->getDomainInfo($domain);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        $isLocked = isset($response['eppStatuses']) && is_array($response['eppStatuses']) &&
            in_array('clientTransferProhibited', $response['eppStatuses'], true);

        return $isLocked ? 'locked' : 'unlocked';
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_SaveRegistrarLock(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $info = $api->getDomainInfo($domain);
        if ($error = handleApiResponse($info)) {
            return $error;
        }

        $isCurrentlyLocked = isset($info['eppStatuses']) && is_array($info['eppStatuses']) &&
            in_array('clientTransferProhibited', $info['eppStatuses'], true);

        $targetLock = !$isCurrentlyLocked;

        $lastError = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            if (Utils::$IsDebugMode) {
                Utils::log("Debug: Updating transfer lock for {$domain}, attempt {$attempt}, targetLock=" . ($targetLock ? 'true' : 'false'));
            }

            $response = $api->updateTransferLock($domain, ['isLocked' => $targetLock]);
            $error    = handleApiResponse($response);

            if (!$error) {
                $check = $api->getDomainInfo($domain);
                if ($checkError = handleApiResponse($check)) {
                    Utils::log('Warning: Could not verify registrar lock after update: ' . $checkError['error'], 'WARNING');
                    return ['success' => true];
                }

                $isNowLocked = isset($check['eppStatuses']) && is_array($check['eppStatuses']) &&
                    in_array('clientTransferProhibited', $check['eppStatuses'], true);

                if ($isNowLocked === $targetLock) {
                    return ['success' => true];
                }

                $lastError = ['error' => 'Registrar lock status verification failed'];
            } else {
                $lastError = $error;
            }

            sleep(2);
        }

        return $lastError ?: ['error' => 'Registrar lock update failed after multiple attempts'];
    } catch (Exception $e) {
        Utils::log('Error updating registrar lock: ' . $e->getMessage(), 'ERROR');
        return ['error' => $e->getMessage()];
    }
}

function spaceship_RegisterDomain(array $params): array
{
    try {
        $api        = initApi($params);
        $domainName = Utils::sanitizeDomain(getDomainName($params));

        if (Utils::$IsDebugMode) {
            Utils::log('Debug: Starting domain registration for: ' . $domainName);
        }

        $contactId = createContactFromParams($api, $params);

        if (Utils::$IsDebugMode) {
            Utils::log('Debug: Created contact ID: ' . $contactId);
        }

        $autoRenew = filter_var($params['autorenew'] ?? $params['AutoRenew'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $privacyLevel = !empty($params['idprotection']) ? 'high' : 'public';
        $userConsent  = true;

        $payload = [
            'autoRenew'         => $autoRenew,
            'years'             => (int)($params['regperiod'] ?? 1),
            'privacyProtection' => [
                'level'       => $privacyLevel,
                'userConsent' => $userConsent,
            ],
            'contacts' => [
                'registrant' => $contactId,
                'admin'      => $contactId,
                'tech'       => $contactId,
                'billing'    => $contactId,
                'attributes' => [],
            ],
        ];

        if (Utils::$IsDebugMode) {
            Utils::log('Debug: Registering domain with payload: ' . json_encode($payload));
        }

        $response = $api->registerDomain($domainName, $payload);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        if (!empty($response['async']) && !empty($response['operationId']) && Utils::$IsDebugMode) {
            Utils::log('Debug: Async registration operation ID: ' . $response['operationId']);
        }

        $nameservers = getNameserversArray($params);
        if (!empty($nameservers) && count($nameservers) >= 2) {
            if (Utils::$IsDebugMode) {
                Utils::log('Debug: Setting nameservers: ' . json_encode($nameservers));
            }

            $nsPayload = [
                'provider' => 'custom',
                'hosts'    => $nameservers,
            ];
            $api->updateNameservers($domainName, $nsPayload);
        }

        return ['success' => true];
    } catch (Exception $e) {
        Utils::log('Error registering domain: ' . $e->getMessage(), 'ERROR');
        return ['error' => $e->getMessage()];
    }
}

function spaceship_TransferDomain(array $params): array
{
    try {
        $api        = initApi($params);
        $domainName = Utils::sanitizeDomain(getDomainName($params));

        $contactId = createContactFromParams($api, $params);

        $autoRenew = filter_var($params['autorenew'] ?? $params['AutoRenew'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        $privacyLevel = !empty($params['idprotection']) ? 'high' : 'public';
        $userConsent  = true;

        $payload = [
            'autoRenew'         => $autoRenew,
            'privacyProtection' => [
                'level'       => $privacyLevel,
                'userConsent' => $userConsent,
            ],
            'contacts' => [
                'registrant' => $contactId,
                'admin'      => $contactId,
                'tech'       => $contactId,
                'billing'    => $contactId,
                'attributes' => [],
            ],
            'authCode' => (string)($params['eppcode'] ?? ''),
        ];

        if (Utils::$IsDebugMode) {
            Utils::log('Debug: Transferring domain with payload: ' . json_encode($payload));
        }

        $response = $api->transferDomain($domainName, $payload);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        if (!empty($response['async']) && !empty($response['operationId']) && Utils::$IsDebugMode) {
            Utils::log('Debug: Async transfer operation ID: ' . $response['operationId']);
        }

        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_RenewDomain(array $params): array
{
    try {
        $api        = initApi($params);
        $domainName = Utils::sanitizeDomain(getDomainName($params));

        $domainInfo = $api->getDomainInfo($domainName);
        if ($error = handleApiResponse($domainInfo)) {
            return $error;
        }

        if (empty($domainInfo['expirationDate'])) {
            return ['error' => 'Expiration date not found in API response'];
        }

        $payload = [
            'years'               => (int)($params['regperiod'] ?? 1),
            'currentExpirationDate' => (string)$domainInfo['expirationDate'],
        ];

        if (Utils::$IsDebugMode) {
            Utils::log('Debug: Renewing domain with payload: ' . json_encode($payload));
        }

        $response = $api->renewDomain($domainName, $payload);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        if (!empty($response['async']) && !empty($response['operationId']) && Utils::$IsDebugMode) {
            Utils::log('Debug: Async renewal operation ID: ' . $response['operationId']);
        }

        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_IDProtectToggle(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $enable = filter_var($params['idprotection'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (Utils::$IsDebugMode) {
            Utils::log("Debug: ID Protection toggle for {$domain}: " . ($enable ? 'ENABLE (high)' : 'DISABLE (public)'));
        }

        $privacyLevel = $enable ? 'high' : 'public';
        $userConsent  = true;

        $privacyResponse = $api->updatePrivacyPreference($domain, $privacyLevel, $userConsent);
        if ($error = handleApiResponse($privacyResponse)) {
            return $error;
        }

        $contactForm = $enable;
        $emailResponse = $api->updateEmailProtectionPreference($domain, $contactForm);
        if ($error = handleApiResponse($emailResponse)) {
            return $error;
        }

        if (Utils::$IsDebugMode) {
            Utils::log("Debug: ID Protection updated successfully - Privacy: {$privacyLevel}, ContactForm: " . ($contactForm ? 'true' : 'false'));
        }

        return ['success' => true];
    } catch (Exception $e) {
        Utils::log('Error toggling ID protection: ' . $e->getMessage(), 'ERROR');
        return ['error' => $e->getMessage()];
    }
}

function spaceship_GetEPPCode(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $response = $api->getAuthCode($domain);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        return [
            'eppcode' => $response['authCode'] ?? '',
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_GetDNS(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $response = $api->getDnsRecords($domain, 200, 0, null);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        $records = $response['items'] ?? $response['records'] ?? [];

        if (!is_array($records)) {
            return ['error' => 'Invalid DNS records response'];
        }

        $result = [];

        foreach ($records as $record) {
            $type     = $record['type'] ?? '';
            $name     = $record['name'] ?? '';
            $value    = $record['address'] ?? $record['value'] ?? '';
            $ttl      = $record['ttl'] ?? 3600;
            $priority = $record['priority'] ?? null;

            if (!$type || !$name) {
                continue;
            }

            $entry = [
                'hostname' => $name,
                'type'     => $type,
                'address'  => $value,
                'priority' => $priority,
                'ttl'      => $ttl,
            ];

            $result[] = $entry;
        }

        return ['dnsrecords' => $result];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_SaveDNS(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $recordsParam = $params['dnsrecords'] ?? [];
        if (!is_array($recordsParam)) {
            return ['error' => 'Invalid dnsrecords parameter'];
        }

        $records = [];

        foreach ($recordsParam as $record) {
            $type     = $record['type'] ?? '';
            $hostname = $record['hostname'] ?? '';
            $address  = $record['address'] ?? '';
            $priority = $record['priority'] ?? null;
            $ttl      = $record['ttl'] ?? 3600;

            if (!$type || !$hostname) {
                continue;
            }

            $records[] = [
                'type'     => $type,
                'name'     => $hostname,
                'address'  => $address,
                'ttl'      => (int)$ttl,
                'priority' => $priority !== null ? (int)$priority : null,
            ];
        }

        $response = $api->saveDnsRecords($domain, $records, true);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        return ['success' => true];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_Sync(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $response = $api->getDomainInfo($domain);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        if (empty($response['expirationDate'])) {
            throw new Exception('Invalid expiration date format.');
        }

        $expirationTimestamp = strtotime($response['expirationDate']);
        if (!$expirationTimestamp) {
            throw new Exception('Invalid expiration date format.');
        }

        $lifecycleStatus = $response['lifecycleStatus'] ?? 'registered';

        $active   = in_array($lifecycleStatus, ['registered', 'pending', 'pendingTransfer'], true);
        $expired  = $lifecycleStatus === 'expired';
        $cancelled = in_array($lifecycleStatus, ['deleted', 'cancelled'], true);
        $transferredAway = $lifecycleStatus === 'transferredAway';

        return [
            'expirydate'      => date('Y-m-d', $expirationTimestamp),
            'active'          => $active,
            'expired'         => $expired,
            'cancelled'       => $cancelled,
            'transferredAway' => $transferredAway,
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_GetDomainInformation(array $params)
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $response = $api->getDomainInfo($domain);
        if ($error = handleApiResponse($response)) {
            throw new Exception($error['error']);
        }

        $nameservers = [];
        if (!empty($response['nameservers']['hosts']) && is_array($response['nameservers']['hosts'])) {
            foreach ($response['nameservers']['hosts'] as $index => $nameserver) {
                $nameservers['ns' . ($index + 1)] = $nameserver;
            }
        }

        $isLocked = isset($response['eppStatuses']) && is_array($response['eppStatuses']) &&
            in_array('clientTransferProhibited', $response['eppStatuses'], true);

        $expirationDate = null;
        if (!empty($response['expirationDate'])) {
            try {
                $expirationDate = Carbon::parse($response['expirationDate']);
            } catch (Exception $e) {
                Utils::log('Invalid expiration date format: ' . $response['expirationDate'], 'ERROR');
            }
        }

        $idProtection    = ($response['privacyProtection']['level'] ?? '') === 'high';
        $dnsManagement   = ($response['nameservers']['provider'] ?? '') === 'basic';
        $emailForwarding = (bool)($response['privacyProtection']['contactForm'] ?? false);

        $domainObj = (new Domain())
            ->setDomain($response['name'] ?? $domain)
            ->setNameservers($nameservers)
            ->setRegistrationStatus($response['verificationStatus'] ?? 'unknown')
            ->setTransferLock($isLocked)
            ->setExpiryDate($expirationDate)
            ->setRestorable(($response['lifecycleStatus'] ?? '') === 'redemption')
            ->setIdProtectionStatus($idProtection)
            ->setDnsManagementStatus($dnsManagement)
            ->setEmailForwardingStatus($emailForwarding);

        return $domainObj;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function spaceship_ToggleAutoRenew(array $params): array
{
    try {
        $api    = initApi($params);
        $domain = Utils::sanitizeDomain(getDomainName($params));

        $enable = filter_var($params['autorenew'] ?? $params['AutoRenew'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (Utils::$IsDebugMode) {
            Utils::log("Debug: Toggling autorenew for {$domain} to " . ($enable ? 'true' : 'false'));
        }

        $response = $api->updateAutoRenew($domain, $enable);
        if ($error = handleApiResponse($response)) {
            return $error;
        }

        return ['success' => true];
    } catch (Exception $e) {
        Utils::log('Error toggling autorenew: ' . $e->getMessage(), 'ERROR');
        return ['error' => $e->getMessage()];
    }
}

function spaceship_RequestDelete(array $params): array
{
    return ['error' => 'Domain deletion must be done directly in Spaceship account'];
}

function spaceship_GetTldPricing(array $params): ResultsList
{
    return new ResultsList();
}