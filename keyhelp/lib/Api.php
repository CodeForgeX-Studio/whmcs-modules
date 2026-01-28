<?php

class Api
{
    private $serverUrl;
    private $apiKey;
    private $lastRequest = [];
    private $lastResponse = [];
    
    public function __construct($params)
    {
        $protocol = $params['serversecure'] ? 'https' : 'http';
        $port = $params['serverport'] ?: '443';
        
        $this->serverUrl = $protocol . '://' . $params['serverhostname'];
        
        if ($port != '443' && $port != '80') {
            $this->serverUrl .= ':' . $port;
        }
        
        $this->serverUrl .= '/api/v2';
        $this->apiKey = $params['serveraccesshash'] ?: $params['serverpassword'];
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null)
    {
        $url = $this->serverUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $this->lastRequest = [
            'url' => $url,
            'method' => $method,
            'data' => $data,
        ];
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            logModuleCall('keyhelp', $endpoint, $this->lastRequest, $error, null);
            throw new Exception('CURL Error: ' . $error);
        }
        
        $this->lastResponse = [
            'code' => $httpCode,
            'body' => $response,
        ];
        
        if ($httpCode == 204) {
            logModuleCall('keyhelp', $endpoint, $this->lastRequest, 'No Content', $httpCode);
            return null;
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? 'Unknown error';
            logModuleCall('keyhelp', $endpoint, $this->lastRequest, $response, $httpCode);
            throw new Exception('API Error (' . $httpCode . '): ' . $errorMsg);
        }
        
        logModuleCall('keyhelp', $endpoint, $this->lastRequest, $response, $httpCode);
        
        return $decoded;
    }
    
    public function getHostingPlans()
    {
        return $this->makeRequest('/hosting-plans?sort=name');
    }
    
    public function createClient($data)
    {
        return $this->makeRequest('/clients', 'POST', $data);
    }
    
    public function getClient($id)
    {
        return $this->makeRequest('/clients/' . $id);
    }
    
    public function getClientByUsername($username)
    {
        return $this->makeRequest('/clients/name/' . urlencode($username));
    }
    
    public function getAllClients()
    {
        return $this->makeRequest('/clients?sort=username');
    }
    
    public function updateClient($id, $data)
    {
        return $this->makeRequest('/clients/' . $id, 'PUT', $data);
    }
    
    public function deleteClient($id)
    {
        return $this->makeRequest('/clients/' . $id, 'DELETE');
    }
    
    public function getClientResources($clientId)
    {
        return $this->makeRequest('/clients/' . $clientId . '/resources');
    }
    
    public function getClientStats($clientId)
    {
        return $this->makeRequest('/clients/' . $clientId . '/stats');
    }
    
    public function getClientTraffic($clientId, $month = null)
    {
        $endpoint = '/clients/' . $clientId . '/traffic';
        if ($month) {
            $endpoint .= '?month=' . $month;
        }
        return $this->makeRequest($endpoint);
    }
    
    public function generateLoginUrl($clientId)
    {
        return $this->makeRequest('/login/' . $clientId);
    }
    
    public function createDomain($data)
    {
        return $this->makeRequest('/domains', 'POST', $data);
    }
    
    public function getDomain($id)
    {
        return $this->makeRequest('/domains/' . $id);
    }
    
    public function getDomainByName($name)
    {
        return $this->makeRequest('/domains/name/' . urlencode($name));
    }
    
    public function updateDomain($id, $data)
    {
        return $this->makeRequest('/domains/' . $id, 'PUT', $data);
    }
    
    public function deleteDomain($id)
    {
        return $this->makeRequest('/domains/' . $id, 'DELETE');
    }
    
    public function getDnsRecords($domainId)
    {
        return $this->makeRequest('/dns/' . $domainId);
    }
    
    public function updateDnsRecords($domainId, $data)
    {
        return $this->makeRequest('/dns/' . $domainId, 'PUT', $data);
    }
    
    public function createCertificate($data)
    {
        return $this->makeRequest('/certificates', 'POST', $data);
    }
    
    public function getCertificate($id)
    {
        return $this->makeRequest('/certificates/' . $id);
    }
    
    public function updateCertificate($id, $data)
    {
        return $this->makeRequest('/certificates/' . $id, 'PUT', $data);
    }
    
    public function deleteCertificate($id)
    {
        return $this->makeRequest('/certificates/' . $id, 'DELETE');
    }
    
    public function createEmail($data)
    {
        return $this->makeRequest('/emails', 'POST', $data);
    }
    
    public function getEmail($id)
    {
        return $this->makeRequest('/emails/' . $id);
    }
    
    public function updateEmail($id, $data)
    {
        return $this->makeRequest('/emails/' . $id, 'PUT', $data);
    }
    
    public function deleteEmail($id)
    {
        return $this->makeRequest('/emails/' . $id, 'DELETE');
    }
    
    public function createDatabase($data)
    {
        return $this->makeRequest('/databases', 'POST', $data);
    }
    
    public function getDatabase($id)
    {
        return $this->makeRequest('/databases/' . $id);
    }
    
    public function updateDatabase($id, $data)
    {
        return $this->makeRequest('/databases/' . $id, 'PUT', $data);
    }
    
    public function deleteDatabase($id)
    {
        return $this->makeRequest('/databases/' . $id, 'DELETE');
    }
    
    public function createFtpUser($data)
    {
        return $this->makeRequest('/ftp-users', 'POST', $data);
    }
    
    public function getFtpUser($id)
    {
        return $this->makeRequest('/ftp-users/' . $id);
    }
    
    public function updateFtpUser($id, $data)
    {
        return $this->makeRequest('/ftp-users/' . $id, 'PUT', $data);
    }
    
    public function deleteFtpUser($id)
    {
        return $this->makeRequest('/ftp-users/' . $id, 'DELETE');
    }
    
    public function getServerInfo()
    {
        return $this->makeRequest('/server');
    }
    
    public function ping()
    {
        return $this->makeRequest('/ping');
    }
}