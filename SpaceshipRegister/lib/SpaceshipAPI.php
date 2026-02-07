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
    private array $headers;

    public function __construct(string $apiKey, string $apiSecret, string $baseUri = 'https://spaceship.dev/api/v1')
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUri = rtrim($baseUri, '/');

        $this->headers = [
            'X-API-Key'    => $this->apiKey,
            'X-API-Secret' => $this->apiSecret,
            'Content-Type' => 'application/json',
        ];
    }

    private function handleRequestException(RequestException $e): array
    {
        $statusCode   = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
        $errorMessage = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();

        return [
            'statusCode' => $statusCode,
            'message'    => $errorMessage,
        ];
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        $client = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
        ]);

        try {
            $response   = $client->request($method, "{$this->baseUri}/{$uri}", $options);
            $statusCode = $response->getStatusCode();
            $body       = $response->getBody()->getContents();
            $headers    = $response->getHeaders();

            $base = [
                'statusCode' => $statusCode,
                'headers'    => $headers,
            ];

            if ($statusCode === 202) {
                $opId = null;
                if (isset($headers['spaceship-async-operationid'][0])) {
                    $opId = $headers['spaceship-async-operationid'][0];
                }

                $base['async'] = true;
                $base['operationId'] = $opId;

                return $base;
            }

            if ($body === '' || $body === null) {
                $base['success'] = ($statusCode >= 200 && $statusCode < 300);
                return $base;
            }

            $decoded = json_decode($body, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                $base['message'] = $body;
                return $base;
            }

            if (!isset($decoded['statusCode'])) {
                $decoded['statusCode'] = $statusCode;
            }

            $decoded['headers'] = $headers;

            return $decoded;
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        }
    }

    public function getAsyncOperation(string $operationId): array
    {
        return $this->request('GET', "async-operations/{$operationId}", [
            'headers' => $this->headers,
        ]);
    }

    public function getDomainInfo(string $domain): array
    {
        return $this->request('GET', "domains/{$domain}", ['headers' => $this->headers]);
    }

    public function registerDomain(string $domain, array $params): array
    {
        return $this->request('POST', "domains/{$domain}", [
            'headers' => $this->headers,
            'json'    => $params,
        ]);
    }

    public function transferDomain(string $domain, array $params): array
    {
        return $this->request('POST', "domains/{$domain}/transfer", [
            'headers' => $this->headers,
            'json'    => $params,
        ]);
    }

    public function renewDomain(string $domain, array $params): array
    {
        return $this->request('POST', "domains/{$domain}/renew", [
            'headers' => $this->headers,
            'json'    => $params,
        ]);
    }

    public function restoreDomain(string $domain): array
    {
        return $this->request('POST', "domains/{$domain}/restore", [
            'headers' => $this->headers,
        ]);
    }

    public function updateNameservers(string $domain, array $nsDetails): array
    {
        return $this->request('PUT', "domains/{$domain}/nameservers", [
            'headers' => $this->headers,
            'json'    => $nsDetails,
        ]);
    }

    public function updateAutoRenew(string $domain, bool $isEnabled): array
    {
        return $this->request('PUT', "domains/{$domain}/autorenew", [
            'headers' => $this->headers,
            'json'    => ['isEnabled' => $isEnabled],
        ]);
    }

    public function checkDomainAvailability(string $domain): array
    {
        return $this->request('GET', "domains/{$domain}/available", ['headers' => $this->headers]);
    }

    public function checkDomainsAvailability(array $domains): array
    {
        return $this->request('POST', 'domains/available', [
            'headers' => $this->headers,
            'json'    => ['domains' => $domains],
        ]);
    }

    public function updateContacts(string $domain, array $contacts): array
    {
        return $this->request('PUT', "domains/{$domain}/contacts", [
            'headers' => $this->headers,
            'json'    => $contacts,
        ]);
    }

    public function getAuthCode(string $domain): array
    {
        return $this->request('GET', "domains/{$domain}/transfer/auth-code", [
            'headers' => $this->headers,
        ]);
    }

    public function updateTransferLock(string $domain, array $lockData): array
    {
        return $this->request('PUT', "domains/{$domain}/transfer/lock", [
            'headers' => $this->headers,
            'json'    => $lockData,
        ]);
    }

    public function getTransferDetails(string $domain): array
    {
        return $this->request('GET', "domains/{$domain}/transfer", [
            'headers' => $this->headers,
        ]);
    }

    public function updatePrivacyPreference(string $domain, string $privacyLevel, bool $userConsent): array
    {
        return $this->request('PUT', "domains/{$domain}/privacy/preference", [
            'headers' => $this->headers,
            'json'    => [
                'privacyLevel' => $privacyLevel,
                'userConsent'  => $userConsent,
            ],
        ]);
    }

    public function updateEmailProtectionPreference(string $domain, bool $contactForm): array
    {
        return $this->request('PUT', "domains/{$domain}/privacy/email-protection-preference", [
            'headers' => $this->headers,
            'json'    => [
                'contactForm' => $contactForm,
            ],
        ]);
    }

    public function getPersonalNameservers(string $domain): array
    {
        return $this->request('GET', "domains/{$domain}/personal-nameservers", [
            'headers' => $this->headers,
        ]);
    }

    public function updatePersonalNameserver(string $domain, string $currentHost, array $config): array
    {
        return $this->request('PUT', "domains/{$domain}/personal-nameservers/{$currentHost}", [
            'headers' => $this->headers,
            'json'    => $config,
        ]);
    }

    public function deletePersonalNameserver(string $domain, string $currentHost): array
    {
        return $this->request('DELETE', "domains/{$domain}/personal-nameservers/{$currentHost}", [
            'headers' => $this->headers,
        ]);
    }

    public function getDomainList(int $take = 50, int $skip = 0, ?array $orderBy = null): array
    {
        $queryParams = ['take' => $take, 'skip' => $skip];
        if ($orderBy) {
            $queryParams['orderBy'] = $orderBy;
        }

        $query = http_build_query($queryParams);

        return $this->request('GET', "domains?{$query}", ['headers' => $this->headers]);
    }

    public function saveContactDetails(array $contactDetails): array
    {
        return $this->request('PUT', 'contacts', [
            'headers' => $this->headers,
            'json'    => $contactDetails,
        ]);
    }

    public function getContactDetails(string $contactId): array
    {
        return $this->request('GET', "contacts/{$contactId}", [
            'headers' => $this->headers,
        ]);
    }

    public function saveContactAttributes(array $attributes): array
    {
        return $this->request('PUT', 'contacts/attributes', [
            'headers' => $this->headers,
            'json'    => $attributes,
        ]);
    }

    public function getContactAttributes(string $contactId): array
    {
        return $this->request('GET', "contacts/attributes/{$contactId}", [
            'headers' => $this->headers,
        ]);
    }

    public function getDnsRecords(string $domain, int $take = 50, int $skip = 0, ?array $orderBy = null): array
    {
        $queryParams = array_filter([
            'take'    => $take,
            'skip'    => $skip,
            'orderBy' => $orderBy,
        ]);

        $query = http_build_query($queryParams);

        return $this->request('GET', "dns/records/{$domain}?{$query}", [
            'headers' => $this->headers,
        ]);
    }

    public function saveDnsRecords(string $domain, array $records, bool $force = false): array
    {
        return $this->request('PUT', "dns/records/{$domain}", [
            'headers' => $this->headers,
            'json'    => [
                'force' => $force,
                'items' => $records,
            ],
        ]);
    }

    public function deleteDnsRecords(string $domain, array $records): array
    {
        return $this->request('DELETE', "dns/records/{$domain}", [
            'headers' => $this->headers,
            'json'    => $records,
        ]);
    }
}