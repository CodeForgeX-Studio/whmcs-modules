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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SpaceshipAPI
{
    private string $baseUri;
    private string $apiKey;
    private string $apiSecret;
    private Client $client;
    private array $headers;

    public function __construct(string $apiKey, string $apiSecret, string $baseUri = 'https://spaceship.dev/api/v1')
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUri = rtrim($baseUri, '/');
        $this->client = new Client();

        $this->headers = [
            'X-API-Key' => $this->apiKey,
            'X-API-Secret' => $this->apiSecret,
            'Content-Type' => 'application/json',
        ];
    }

    private function handleRequestException(RequestException $e): array
    {
        $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
        $errorMessage = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();

        return [
            'statusCode' => $statusCode,
            'message' => $errorMessage,
        ];
    }

    private function request(string $method, string $uri, array $options = [])
    {
        try {
            $response = $this->client->request($method, "$this->baseUri/$uri", $options);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            if ($statusCode === 202) {
                return ['statusCode' => 200, 'success' => true];
            }
            
            if ($statusCode === 204) {
                return ['statusCode' => 200, 'success' => true];
            }
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $data = json_decode($body, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    return ['statusCode' => 200, 'success' => true];
                }
                return $data;
            }
            
            return json_decode($body, true);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        }
    }

    /**
     * Get domain information
     * GET /v1/domains/{domain}
     */
    public function getDomainInfo(string $domain): array
    {
        return $this->request('GET', "domains/$domain", ['headers' => $this->headers]);
    }

    /**
     * Register a domain
     * POST /v1/domains/{domain}
     */
    public function registerDomain(string $domain, array $params): array
    {
        return $this->request('POST', "domains/$domain", [
            'headers' => $this->headers,
            'json' => $params
        ]);
    }

    /**
     * Transfer a domain
     * POST /v1/domains/{domain}/transfer
     */
    public function transferDomain(string $domain, array $params): array
    {
        return $this->request('POST', "domains/$domain/transfer", [
            'headers' => $this->headers,
            'json' => $params
        ]);
    }

    /**
     * Renew a domain
     * POST /v1/domains/{domain}/renew
     */
    public function renewDomain(string $domain, array $params): array
    {
        return $this->request('POST', "domains/$domain/renew", [
            'headers' => $this->headers,
            'json' => $params
        ]);
    }

    /**
     * Update domain nameservers
     * PUT /v1/domains/{domain}/nameservers
     */
    public function updateNameservers(string $domain, array $nsDetails): array
    {
        return $this->request('PUT', "domains/$domain/nameservers", [
            'headers' => $this->headers,
            'json' => $nsDetails
        ]);
    }

    /**
     * Update domain autorenewal state
     * PUT /v1/domains/{domain}/autorenew
     */
    public function updateAutoRenew(string $domain, bool $isEnabled): array
    {
        return $this->request('PUT', "domains/$domain/autorenew", [
            'headers' => $this->headers,
            'json' => ['isEnabled' => $isEnabled]
        ]);
    }

    /**
     * Check domain availability
     * GET /v1/domains/{domain}/available
     */
    public function checkDomainAvailability(string $domain): array
    {
        return $this->request('GET', "domains/$domain/available", ['headers' => $this->headers]);
    }

    /**
     * Check multiple domains availability
     * POST /v1/domains/available
     */
    public function checkDomainsAvailability(array $domains): array
    {
        return $this->request('POST', "domains/available", [
            'headers' => $this->headers,
            'json' => ['domains' => $domains]
        ]);
    }

    /**
     * Update domain contacts
     * PUT /v1/domains/{domain}/contacts
     */
    public function updateContacts(string $domain, array $contacts): array
    {
        return $this->request('PUT', "domains/$domain/contacts", [
            'headers' => $this->headers,
            'json' => $contacts
        ]);
    }

    /**
     * Get domain auth code (EPP code)
     * GET /v1/domains/{domain}/transfer/auth-code
     */
    public function getAuthCode(string $domain): array
    {
        return $this->request('GET', "domains/$domain/transfer/auth-code", ['headers' => $this->headers]);
    }

    /**
     * Update domain transfer lock
     * PUT /v1/domains/{domain}/transfer/lock
     */
    public function updateTransferLock(string $domain, array $lockData): array
    {
        return $this->request('PUT', "domains/$domain/transfer/lock", [
            'headers' => $this->headers,
            'json' => $lockData
        ]);
    }

    /**
     * Get domain transfer details
     * GET /v1/domains/{domain}/transfer
     */
    public function getTransferDetails(string $domain): array
    {
        return $this->request('GET', "domains/$domain/transfer", ['headers' => $this->headers]);
    }

    /**
     * Update domain privacy preference
     * PUT /v1/domains/{domain}/privacy/preference
     */
    public function updatePrivacyPreference(string $domain, string $privacyLevel, bool $userConsent): array
    {
        return $this->request('PUT', "domains/$domain/privacy/preference", [
            'headers' => $this->headers,
            'json' => [
                'privacyLevel' => $privacyLevel,
                'userConsent' => $userConsent
            ]
        ]);
    }

    /**
     * Update domain email protection preference
     * PUT /v1/domains/{domain}/privacy/email-protection-preference
     */
    public function updateEmailProtectionPreference(string $domain, bool $contactForm): array
    {
        return $this->request('PUT', "domains/$domain/privacy/email-protection-preference", [
            'headers' => $this->headers,
            'json' => ['contactForm' => $contactForm]
        ]);
    }

    /**
     * Restore domain
     * POST /v1/domains/{domain}/restore
     */
    public function restoreDomain(string $domain): array
    {
        return $this->request('POST', "domains/$domain/restore", ['headers' => $this->headers]);
    }

    /**
     * Get personal nameservers
     * GET /v1/domains/{domain}/personal-nameservers
     */
    public function getPersonalNameservers(string $domain): array
    {
        return $this->request('GET', "domains/$domain/personal-nameservers", ['headers' => $this->headers]);
    }

    /**
     * Update personal nameservers host configuration
     * PUT /v1/domains/{domain}/personal-nameservers/{currentHost}
     */
    public function updatePersonalNameserver(string $domain, string $currentHost, array $config): array
    {
        return $this->request('PUT', "domains/$domain/personal-nameservers/$currentHost", [
            'headers' => $this->headers,
            'json' => $config
        ]);
    }

    /**
     * Delete personal nameservers host configuration
     * DELETE /v1/domains/{domain}/personal-nameservers/{currentHost}
     */
    public function deletePersonalNameserver(string $domain, string $currentHost): array
    {
        return $this->request('DELETE', "domains/$domain/personal-nameservers/$currentHost", [
            'headers' => $this->headers
        ]);
    }

    /**
     * Get domain list
     * GET /v1/domains
     */
    public function getDomainList(int $take = 50, int $skip = 0, ?array $orderBy = null): array
    {
        $queryParams = ['take' => $take, 'skip' => $skip];
        if ($orderBy) {
            $queryParams['orderBy'] = $orderBy;
        }
        $query = http_build_query($queryParams);
        return $this->request('GET', "domains?$query", ['headers' => $this->headers]);
    }

    /**
     * Save contact details
     * PUT /v1/contacts
     */
    public function saveContactDetails(array $contactDetails): array
    {
        return $this->request('PUT', "contacts", [
            'headers' => $this->headers,
            'json' => $contactDetails
        ]);
    }

    /**
     * Read contact details
     * GET /v1/contacts/{contact}
     */
    public function getContactDetails(string $contactId): array
    {
        return $this->request('GET', "contacts/$contactId", ['headers' => $this->headers]);
    }

    /**
     * Save contact attributes
     * PUT /v1/contacts/attributes
     */
    public function saveContactAttributes(array $attributes): array
    {
        return $this->request('PUT', "contacts/attributes", [
            'headers' => $this->headers,
            'json' => $attributes
        ]);
    }

    /**
     * Read contact attribute details
     * GET /v1/contacts/attributes/{contact}
     */
    public function getContactAttributes(string $contactId): array
    {
        return $this->request('GET', "contacts/attributes/$contactId", ['headers' => $this->headers]);
    }

    /**
     * Get DNS records list
     * GET /v1/dns/records/{domain}
     */
    public function getDnsRecords(string $domain, int $take = 50, int $skip = 0, ?array $orderBy = null): array
    {
        $queryParams = array_filter([
            'take' => $take,
            'skip' => $skip,
            'orderBy' => $orderBy
        ]);
        $query = http_build_query($queryParams);
        return $this->request('GET', "dns/records/$domain?$query", ['headers' => $this->headers]);
    }

    /**
     * Save DNS resource records
     * PUT /v1/dns/records/{domain}
     */
    public function saveDnsRecords(string $domain, array $records, bool $force = false): array
    {
        return $this->request('PUT', "dns/records/$domain", [
            'headers' => $this->headers,
            'json' => [
                'force' => $force,
                'items' => $records
            ]
        ]);
    }

    /**
     * Delete DNS resource records
     * DELETE /v1/dns/records/{domain}
     */
    public function deleteDnsRecords(string $domain, array $records): array
    {
        return $this->request('DELETE', "dns/records/$domain", [
            'headers' => $this->headers,
            'json' => $records
        ]);
    }
}