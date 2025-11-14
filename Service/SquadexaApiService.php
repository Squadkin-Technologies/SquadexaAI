<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Logger\Logger as SquadexaLogger;

class SquadexaApiService
{
    const API_BASE_URL = 'https://squadexa.ai/';
    const REDIRECT_URL = 'https://squadexa.ai/';
    const API_KEY_CONFIG = 'squadexaiproductcreator/authentication/api_key';
    
    const API_ENDPOINTS = [
        // Authentication endpoints
        'register' => '/api/v1/auth/register',
        'login' => '/api/v1/auth/login',
        'regenerate_api_key' => '/api/v1/auth/regenerate-api-key',
        'api_key_metadata' => '/api/v1/auth/api-key',
        'user_profile' => '/api/v1/auth/me',
        
        // Usage Statistics endpoints (correct ones!)
        'usage_stats' => '/api/v1/usage-stats',
        'usage_history' => '/api/v1/usage-history',
        
        // Health check endpoints
        'health_check' => '/api/v1/health',
        'health_ready' => '/api/v1/health/ready',
        'health_live' => '/api/v1/health/live',
        'health_detailed' => '/api/v1/health/detailed',
        
        // Product generation endpoints (Core functionality)
        'product_details' => '/api/v1/product-details',
        'batch_jobs' => '/api/v1/batch-jobs',
        'job_status' => '/api/v1/job-status',
        'job_download' => '/api/v1/job-download',
        
        // Billing endpoints
        'billing_plans' => '/api/v1/billing/plans',
        'billing_subscription' => '/api/v1/billing/subscription',
        'billing_history' => '/api/v1/billing/history',
        'billing_config' => '/api/v1/billing/config'
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SquadexaLogger
     */
    private $squadexaLogger;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param SquadexaLogger $squadexaLogger
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        Json $jsonSerializer,
        LoggerInterface $logger,
        SquadexaLogger $squadexaLogger,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->squadexaLogger = $squadexaLogger;
        $this->encryptor = $encryptor;
    }

    /**
     * Get API base URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return self::API_BASE_URL;
    }

    /**
     * Get API key from configuration (decrypted)
     *
     * @return string
     */
    public function getApiKey(): string
    {
        $encryptedKey = $this->scopeConfig->getValue(self::API_KEY_CONFIG);
        if (!$encryptedKey) {
            return '';
        }
        return $this->encryptor->decrypt($encryptedKey);
    }

    /**
     * Get access token from configuration
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->scopeConfig->getValue('squadexaiproductcreator/authentication/access_token') ?: '';
    }

    /**
     * Get username from configuration
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->scopeConfig->getValue('squadexaiproductcreator/authentication/username') ?: '';
    }

    /**
     * Get password from configuration (decrypted)
     *
     * @return string
     */
    public function getPassword(): string
    {
        $encryptedPassword = $this->scopeConfig->getValue('squadexaiproductcreator/authentication/password');
        if (!$encryptedPassword) {
            return '';
        }
        return $this->encryptor->decrypt($encryptedPassword);
    }

    /**
     * Get redirect URL for account creation
     *
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return self::REDIRECT_URL;
    }

    /**
     * Generate permanent API key using access token
     *
     * @param string $accessToken
     * @return array
     * @throws LocalizedException
     */
    public function generateApiKey(string $accessToken): array
    {
        $this->logger->info('SquadexaAI: Generating permanent API key', [
            'access_token_length' => strlen($accessToken)
        ]);

        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . self::API_ENDPOINTS['regenerate_api_key'];

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ]);

        $this->logger->info('SquadexaAI Generate API Key Request', [
            'url' => $url,
            'access_token_length' => strlen($accessToken)
        ]);

        try {
            $this->curl->post($url, '{}');
            
            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->logger->info('SquadexaAI Generate API Key Response', [
                'url' => $url,
                'status' => $responseCode,
                'response_length' => strlen($responseBody),
                'response_preview' => substr($responseBody, 0, 500)
            ]);

            if ($responseCode >= 200 && $responseCode < 300) {
                try {
                    $decodedResponse = $this->jsonSerializer->unserialize($responseBody);
                    $this->logger->info('SquadexaAI Generate API Key Success', [
                        'url' => $url,
                        'response_data' => $decodedResponse
                    ]);
                    return $decodedResponse;
                } catch (\Exception $jsonError) {
                    $this->logger->error('SquadexaAI Generate API Key JSON Parse Error', [
                        'url' => $url,
                        'response_body' => $responseBody,
                        'json_error' => $jsonError->getMessage()
                    ]);
                    throw new LocalizedException(__('Invalid JSON response from generate API key: %1', $jsonError->getMessage()));
                }
            } else {
                $errorMessage = $this->getErrorMessage($responseCode, $responseBody);
                $this->logger->error('SquadexaAI Generate API Key Error Response', [
                    'url' => $url,
                    'status' => $responseCode,
                    'response' => $responseBody,
                    'error_message' => $errorMessage
                ]);
                throw new LocalizedException(__('Generate API key failed: %1', $errorMessage));
            }
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Generate API Key Exception', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('Generate API key request failed: %1', $e->getMessage()));
        }
    }

    /**
     * Check if access token is expired and refresh if needed
     * Only refresh if we have a valid API key (permanent) to avoid unnecessary logins
     *
     * @return bool
     * @throws LocalizedException
     */
    public function refreshAccessTokenIfNeeded(): bool
    {
        $accessToken = $this->getAccessToken();
        $apiKey = $this->getApiKey();
        
        // If we have an API key (permanent), we don't need to refresh access token
        if (!empty($apiKey) && !empty($accessToken)) {
            $this->squadexaLogger->logDebug('API key available, skipping access token refresh', [
                'api_key_length' => strlen($apiKey),
                'access_token_length' => strlen($accessToken)
            ]);
            return true;
        }
        
        // If no access token, we can't refresh
        if (empty($accessToken)) {
            $this->squadexaLogger->logDebug('No access token available, cannot refresh');
            return false;
        }
        
        // Check if token is expired (30 minutes = 1800 seconds)
        $tokenCreated = $this->scopeConfig->getValue('squadexaiproductcreator/authentication/token_created');
        $this->squadexaLogger->logDebug('Token creation timestamp', [
            'token_created' => $tokenCreated
        ]);
        
        if (empty($tokenCreated)) {
            $this->squadexaLogger->logDebug('No token creation timestamp found, assuming expired');
            return false;
        }
        
        $tokenAge = time() - strtotime($tokenCreated);
        if ($tokenAge < 1800) { // Token is still valid (less than 30 minutes)
            $this->squadexaLogger->logDebug('Access token still valid', [
                'token_age_minutes' => floor($tokenAge / 60),
                'remaining_minutes' => floor((1800 - $tokenAge) / 60)
            ]);
            return true;
        }
        
        // Token is expired, but we should not auto-refresh
        // User should manually click "Generate API Key" button
        $this->squadexaLogger->logWarning('Access token expired, manual refresh required', [
            'token_age_minutes' => floor($tokenAge / 60)
        ]);
        
        return false;
    }

    /**
     * Update access token in configuration
     *
     * @param string $accessToken
     * @return void
     */
    private function updateAccessToken(string $accessToken): void
    {
        // This would typically be done through Magento's configuration save
        // For now, we'll log the need to update the token
        $this->logger->info('SquadexaAI: Access token needs to be updated in configuration', [
            'new_token_length' => strlen($accessToken),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get token created timestamp
     *
     * @return string
     */
    public function getTokenCreated(): string
    {
        return (string)$this->scopeConfig->getValue('squadexaiproductcreator/authentication/token_created');
    }

    /**
     * Login with username and password to get access token
     *
     * @param string $username
     * @param string $password
     * @return array
     * @throws LocalizedException
     */
    public function login(string $username, string $password): array
    {
        $this->squadexaLogger->logAuth('Login attempt', [
            'username' => $username,
            'password' => $password
        ]);

        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . '/api/v1/auth/login';

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);

        $loginData = [
            'email' => $username,
            'password' => $password
        ];

        $this->squadexaLogger->logApiRequest('Login request', [
            'url' => $url,
            'username' => $username
        ]);

        try {
            $this->curl->post($url, $this->jsonSerializer->serialize($loginData));
            
            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->squadexaLogger->logApiResponse('Login response', [
                'url' => $url,
                'status' => $responseCode,
                'response_length' => strlen($responseBody),
                'response_preview' => substr($responseBody, 0, 500)
            ]);

            if ($responseCode >= 200 && $responseCode < 300) {
                try {
                    $decodedResponse = $this->jsonSerializer->unserialize($responseBody);
                    $this->squadexaLogger->logAuth('Login successful', [
                        'url' => $url,
                        'response_data' => $decodedResponse
                    ]);
                    return $decodedResponse;
                } catch (\Exception $jsonError) {
                    $this->squadexaLogger->logApiError('Login JSON parse error', [
                        'url' => $url,
                        'response_body' => $responseBody,
                        'json_error' => $jsonError->getMessage()
                    ]);
                    throw new LocalizedException(__('Invalid JSON response from login API: %1', $jsonError->getMessage()));
                }
            } else {
                $errorMessage = $this->getErrorMessage($responseCode, $responseBody);
                $this->squadexaLogger->logApiError('Login error response', [
                    'url' => $url,
                    'status' => $responseCode,
                    'response' => $responseBody,
                    'error_message' => $errorMessage
                ]);
                throw new LocalizedException(__('Login failed: %1', $errorMessage));
            }
        } catch (\Exception $e) {
            $this->squadexaLogger->logApiError('Login exception', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('Login request failed: %1', $e->getMessage()));
        }
    }

    /**
     * Make API request to Squadexa AI
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function makeApiRequest(string $endpoint, string $method = 'GET', array $data = []): array
    {
        // Try access token first, then fall back to API key
        $accessToken = $this->getAccessToken();
        $apiKey = $this->getApiKey();
        
        if (empty($accessToken) && empty($apiKey)) {
            $this->logger->error('SquadexaAI API Error: No authentication configured');
            throw new LocalizedException(__('No authentication configured. Please either set an API key or generate an access token.'));
        }

        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . $endpoint;

        // Use access token if available, otherwise use API key
        $authToken = !empty($accessToken) ? $accessToken : $apiKey;
        $authType = !empty($accessToken) ? 'access_token' : 'api_key';

        // Set headers
        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $authToken,
            'Accept' => 'application/json'
        ]);

        $this->logger->info('SquadexaAI API Request Starting', [
            'url' => $url,
            'method' => $method,
            'auth_type' => $authType,
            'auth_token_length' => strlen($authToken),
            'auth_token_prefix' => substr($authToken, 0, 10) . '...'
        ]);

        try {
            if ($method === 'GET') {
                $this->curl->get($url);
            } else {
                $this->curl->post($url, $this->jsonSerializer->serialize($data));
            }

            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->logger->info('SquadexaAI API Response', [
                'url' => $url,
                'method' => $method,
                'status' => $responseCode,
                'response_length' => strlen($responseBody),
                'response_preview' => substr($responseBody, 0, 500)
            ]);

            if ($responseCode >= 200 && $responseCode < 300) {
                try {
                    $decodedResponse = $this->jsonSerializer->unserialize($responseBody);
                    $this->logger->info('SquadexaAI API Success', [
                        'url' => $url,
                        'response_data' => $decodedResponse
                    ]);
                    return $decodedResponse;
                } catch (\Exception $jsonError) {
                    $this->logger->error('SquadexaAI JSON Parse Error', [
                        'url' => $url,
                        'response_body' => $responseBody,
                        'json_error' => $jsonError->getMessage()
                    ]);
                    throw new LocalizedException(__('Invalid JSON response from API: %1', $jsonError->getMessage()));
                }
            } else {
                $errorMessage = $this->getErrorMessage($responseCode, $responseBody);
                $this->logger->error('SquadexaAI API Error Response', [
                    'url' => $url,
                    'status' => $responseCode,
                    'response' => $responseBody,
                    'error_message' => $errorMessage
                ]);
                throw new LocalizedException(__('API request failed: %1', $errorMessage));
            }

        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI API Exception', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('API request failed: %1', $e->getMessage()));
        }
    }

    /**
     * Get usage history
     *
     * @return array
     * @throws LocalizedException
     */
    public function getUsageHistory(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['usage_history']);
    }

    /**
     * Get general usage stats (requires API key)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getGeneralUsageStats(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['usage_stats_general']);
    }

    /**
     * Get general usage history (requires API key)
     *
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws LocalizedException
     */
    public function getGeneralUsageHistory(int $limit = 20, int $offset = 0): array
    {
        $endpoint = self::API_ENDPOINTS['usage_history_general'] . '?limit=' . $limit . '&offset=' . $offset;
        return $this->makeApiRequestWithApiKey($endpoint, 'GET');
    }

    /**
     * Get user profile (requires API key)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getUserProfile(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['user_profile']);
    }

    /**
     * Get API key metadata (requires API key)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getApiKeyMetadata(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['api_key_metadata']);
    }

    /**
     * Get usage stats (requires API key)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getUsageStats(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['usage_stats']);
    }

    /**
     * Get billing subscription (requires API key)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getBillingSubscription(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['billing_subscription']);
    }

    /**
     * Generate single product (requires API key)
     *
     * @param array $productData
     * @return array
     * @throws LocalizedException
     */
    public function generateProduct(array $productData): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['product_details'], 'POST', $productData);
    }


    /**
     * Create batch job (requires API key)
     *
     * @param array $batchData
     * @return array
     * @throws LocalizedException
     */
    /**
     * Create batch job with file upload (requires API key)
     *
     * @param string $filePath Full path to the CSV file to upload
     * @return array Response containing job_id, total_items, status
     * @throws LocalizedException
     */
    public function createBatchJobWithFile(string $filePath): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new LocalizedException(__('API key is not configured. Please generate an API key first.'));
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new LocalizedException(__('File does not exist or is not readable: %1', $filePath));
        }

        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . self::API_ENDPOINTS['batch_jobs'];

        // Prepare multipart form data for file upload
        $boundary = uniqid();
        $fileName = basename($filePath);
        $fileContent = file_get_contents($filePath);

        $body = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$fileName}\"\r\n";
        $body .= "Content-Type: text/csv\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $this->curl->setHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'Accept' => 'application/json'
        ]);

        $this->logger->info('SquadexaAI API: Creating batch job with file upload', [
            'url' => $url,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => strlen($fileContent)
        ]);

        try {
            $this->curl->post($url, $body);
            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->logger->info('SquadexaAI API: Batch job creation response', [
                'status' => $responseCode,
                'response_length' => strlen($responseBody),
                'response_preview' => substr($responseBody, 0, 500)
            ]);

            if ($responseCode >= 200 && $responseCode < 300) {
                $decodedResponse = $this->jsonSerializer->unserialize($responseBody);
                $this->logger->info('SquadexaAI API: Batch job created successfully', [
                    'job_id' => $decodedResponse['job_id'] ?? 'N/A',
                    'total_items' => $decodedResponse['total_items'] ?? 0,
                    'status' => $decodedResponse['status'] ?? 'N/A'
                ]);
                return $decodedResponse;
            } else {
                $errorMessage = $this->getErrorMessage($responseCode, $responseBody);
                $this->logger->error('SquadexaAI API: Batch job creation failed', [
                    'status' => $responseCode,
                    'error' => $errorMessage,
                    'response' => $responseBody
                ]);
                throw new LocalizedException(__('Failed to create batch job: %1', $errorMessage));
            }
        } catch (\JsonException $e) {
            $this->logger->error('SquadexaAI API: JSON parse error in batch job creation', [
                'error' => $e->getMessage(),
                'response_body' => $responseBody ?? ''
            ]);
            throw new LocalizedException(__('Invalid JSON response from API: %1', $e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI API: Exception in batch job creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('Failed to create batch job: %1', $e->getMessage()));
        }
    }

    /**
     * Create batch job (legacy method - kept for compatibility)
     *
     * @param array $batchData
     * @return array
     * @throws LocalizedException
     */
    public function createBatchJob(array $batchData): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['batch_jobs'], 'POST', $batchData);
    }

    /**
     * Get job status (requires API key)
     *
     * @param string $jobId
     * @return array
     * @throws LocalizedException
     */
    public function getJobStatus(string $jobId): array
    {
        $endpoint = self::API_ENDPOINTS['job_status'] . '/' . $jobId;
        return $this->makeApiRequestWithApiKey($endpoint, 'GET');
    }

    /**
     * Download job results as CSV content (requires API key)
     *
     * @param string $jobId
     * @return string CSV content as string
     * @throws LocalizedException
     */
    public function downloadJobResults(string $jobId): string
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new LocalizedException(__('API key is not configured. Please generate an API key first.'));
        }

        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $endpoint = self::API_ENDPOINTS['job_download'] . '/' . $jobId;
        $url = $baseUrl . $endpoint;

        $this->curl->setHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'text/csv, application/json'
        ]);

        $this->logger->info('SquadexaAI API: Downloading job results', [
            'url' => $url,
            'job_id' => $jobId
        ]);

        try {
            $this->curl->get($url);
            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->logger->info('SquadexaAI API: Job download response', [
                'status' => $responseCode,
                'response_size' => strlen($responseBody)
            ]);

            if ($responseCode >= 200 && $responseCode < 300) {
                // Return CSV content as string
                return $responseBody;
            } else {
                $errorMessage = $this->getErrorMessage($responseCode, $responseBody);
                $this->logger->error('SquadexaAI API: Job download failed', [
                    'status' => $responseCode,
                    'error' => $errorMessage
                ]);
                throw new LocalizedException(__('Failed to download job results: %1', $errorMessage));
            }
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI API: Exception downloading job results', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('Failed to download job results: %1', $e->getMessage()));
        }
    }

    /**
     * Make API request with access token authentication
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function makeApiRequestWithAccessToken(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            throw new LocalizedException(__('Access token is not configured. Please generate an access token first.'));
        }

        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . $endpoint;

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ]);

        $this->logger->info('SquadexaAI API Request (Access Token)', [
            'url' => $url,
            'method' => $method,
            'access_token_length' => strlen($accessToken)
        ]);

        try {
            return $this->executeApiRequest($url, $method, $data);
        } catch (LocalizedException $e) {
            // Check if it's a 401 error (token expired)
            if (strpos($e->getMessage(), '401') !== false || strpos($e->getMessage(), 'Authentication required') !== false) {
                $this->squadexaLogger->logApiError('Access token expired, manual refresh required', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
                throw new LocalizedException(__('Access token expired. Please click "Generate API Key" to refresh your authentication.'));
            }
            
            // Re-throw the original exception if it's not a 401 error
            throw $e;
        }
    }

    /**
     * Make API request with API key authentication
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function makeApiRequestWithApiKey(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new LocalizedException(__('API key is not configured. Please generate an API key first.'));
        }

        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . $endpoint;

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json'
        ]);

        $this->logger->info('SquadexaAI API Request (API Key)', [
            'url' => $url,
            'method' => $method,
            'api_key_length' => strlen($apiKey)
        ]);

        return $this->executeApiRequest($url, $method, $data);
    }

    /**
     * Make API request without authentication
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function makeApiRequestWithoutAuth(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . $endpoint;

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);

        $this->logger->info('SquadexaAI API Request (No Auth)', [
            'url' => $url,
            'method' => $method
        ]);

        return $this->executeApiRequest($url, $method, $data);
    }

    /**
     * Execute API request
     *
     * @param string $url
     * @param string $method
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    private function executeApiRequest(string $url, string $method, array $data): array
    {
        try {
            if ($method === 'GET') {
                $this->curl->get($url);
            } else {
                $this->curl->post($url, $this->jsonSerializer->serialize($data));
            }

            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->logger->info('SquadexaAI API Response', [
                'url' => $url,
                'method' => $method,
                'status' => $responseCode,
                'response_length' => strlen($responseBody)
            ]);

            if ($responseCode >= 200 && $responseCode < 300) {
                try {
                    $decodedResponse = $this->jsonSerializer->unserialize($responseBody);
                    $this->logger->info('SquadexaAI API Success', [
                        'url' => $url,
                        'response_data' => $decodedResponse
                    ]);
                    return $decodedResponse;
                } catch (\Exception $jsonError) {
                    $this->logger->error('SquadexaAI JSON Parse Error', [
                        'url' => $url,
                        'response_body' => $responseBody,
                        'json_error' => $jsonError->getMessage()
                    ]);
                    throw new LocalizedException(__('Invalid JSON response from API: %1', $jsonError->getMessage()));
                }
            } else {
                $errorMessage = $this->getErrorMessage($responseCode, $responseBody);
                $this->logger->error('SquadexaAI API Error Response', [
                    'url' => $url,
                    'status' => $responseCode,
                    'response' => $responseBody,
                    'error_message' => $errorMessage
                ]);
                throw new LocalizedException(__('API request failed: %1', $errorMessage));
            }
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI API Exception', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('API request failed: %1', $e->getMessage()));
        }
    }

    /**
     * Check API health (no authentication required)
     *
     * @return array
     * @throws LocalizedException
     */
    public function checkHealth(): array
    {
        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $url = $baseUrl . self::API_ENDPOINTS['health_check'];

        // Set headers (no authentication for health check)
        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);

        $this->squadexaLogger->logHealthCheck('Health check request', ['url' => $url]);

        try {
            $this->curl->get($url);
            $responseCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->squadexaLogger->logHealthCheck('Health check response', [
                'url' => $url,
                'status' => $responseCode,
                'response' => $responseBody
            ]);

            if ($responseCode >= 200 && $responseCode < 300) {
                return $this->jsonSerializer->unserialize($responseBody);
            } else {
                throw new LocalizedException(__('Health check failed with status: %1', $responseCode));
            }

        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Health Check Error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw new LocalizedException(__('Health check failed: %1', $e->getMessage()));
        }
    }

    /**
     * Check API readiness
     *
     * @return array
     * @throws LocalizedException
     */
    public function checkReadiness(): array
    {
        return $this->makeApiRequestWithoutAuth(self::API_ENDPOINTS['health_ready']);
    }

    /**
     * Check API liveness
     *
     * @return array
     * @throws LocalizedException
     */
    public function checkLiveness(): array
    {
        return $this->makeApiRequestWithoutAuth(self::API_ENDPOINTS['health_live']);
    }

    /**
     * Get detailed health check
     *
     * @return array
     * @throws LocalizedException
     */
    public function getDetailedHealth(): array
    {
        return $this->makeApiRequestWithoutAuth(self::API_ENDPOINTS['health_detailed']);
    }

    /**
     * Validate API key
     *
     * @return bool
     */
    public function validateApiKey(): bool
    {
        try {
            $this->getApiKeyMetadata();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('API Key validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get account information including usage and subscription details
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAccountInformation(): array
    {
        $accountInfo = [
            'api_key_valid' => false,
            'user_profile' => null,
            'usage_stats' => null,
            'subscription_plan' => null,
            'error' => null
        ];

        try {
            // First try to use API key (permanent) if available
            $apiKey = $this->getApiKey();
            if (!empty($apiKey)) {
                try {
                    $apiKeyMetadata = $this->getApiKeyMetadata();
                    $accountInfo['api_key_valid'] = true;
                    $accountInfo['api_key_metadata'] = $apiKeyMetadata;
                    
                    // Get user profile using API key
                    $userProfile = $this->getUserProfile();
                    $accountInfo['user_profile'] = $userProfile;

                    // Get usage statistics using API key
                    $usageStats = $this->getUsageStats();
                    $accountInfo['usage_stats'] = $usageStats;

                    // Extract subscription plan from user profile or usage stats
                    $accountInfo['subscription_plan'] = $this->extractSubscriptionPlan($userProfile, $usageStats);
                    
                } catch (\Exception $apiKeyError) {
                    $this->squadexaLogger->logApiError('API key validation failed', ['error' => $apiKeyError->getMessage()]);
                    $accountInfo['api_key_valid'] = false;
                    $accountInfo['error'] = 'API key validation failed: ' . $apiKeyError->getMessage();
                }
            } else {
                // Fallback to access token if no API key
                $accessToken = $this->getAccessToken();
                if (!empty($accessToken)) {
                    try {
                        // Get user profile using access token
                        $userProfile = $this->getUserProfile();
                        $accountInfo['user_profile'] = $userProfile;
                        $accountInfo['api_key_valid'] = true; // Access token is valid

                        // Get usage statistics using access token
                        $usageStats = $this->getUsageStats();
                        $accountInfo['usage_stats'] = $usageStats;

                        // Extract subscription plan from user profile or usage stats
                        $accountInfo['subscription_plan'] = $this->extractSubscriptionPlan($userProfile, $usageStats);
                        
                    } catch (\Exception $accessTokenError) {
                        $this->squadexaLogger->logApiError('Access token validation failed', ['error' => $accessTokenError->getMessage()]);
                        $accountInfo['api_key_valid'] = false;
                        $accountInfo['error'] = 'Access token validation failed: ' . $accessTokenError->getMessage();
                    }
                } else {
                    $accountInfo['api_key_valid'] = false;
                    $accountInfo['error'] = 'No authentication configured. Please generate an API key.';
                }
            }

        } catch (\Exception $e) {
            $accountInfo['error'] = $e->getMessage();
            $this->squadexaLogger->logApiError('Failed to get account information', ['error' => $e->getMessage()]);
        }

        return $accountInfo;
    }

    /**
     * Extract subscription plan information
     *
     * @param array $userProfile
     * @param array $usageStats
     * @return array
     */
    private function extractSubscriptionPlan(array $userProfile, array $usageStats): array
    {
        $plan = [
            'name' => 'FREE',
            'calls_limit' => 5,
            'calls_remaining' => 5,
            'period' => 'month'
        ];

        // Extract from usage stats if available
        if (isset($usageStats['plan'])) {
            $plan = array_merge($plan, $usageStats['plan']);
        }

        // Extract from user profile if available
        if (isset($userProfile['subscription'])) {
            $plan = array_merge($plan, $userProfile['subscription']);
        }

        // Calculate remaining calls
        if (isset($usageStats['calls_used']) && isset($plan['calls_limit'])) {
            $plan['calls_remaining'] = max(0, $plan['calls_limit'] - $usageStats['calls_used']);
        }

        return $plan;
    }

    /**
     * Get error message from API response
     *
     * @param int $statusCode
     * @param string $responseBody
     * @return string
     */
    private function getErrorMessage(int $statusCode, string $responseBody): string
    {
        try {
            $response = $this->jsonSerializer->unserialize($responseBody);
            if (is_array($response)) {
                if (isset($response['message'])) {
                    return is_string($response['message']) ? $response['message'] : (string)$response['message'];
                }
                if (isset($response['error'])) {
                    if (is_array($response['error'])) {
                        return implode(', ', array_filter($response['error'], 'is_string'));
                    }
                    return is_string($response['error']) ? $response['error'] : (string)$response['error'];
                }
                if (isset($response['errors']) && is_array($response['errors'])) {
                    return implode(', ', array_filter($response['errors'], 'is_string'));
                }
            }
        } catch (\Exception $e) {
            // If we can't parse the response, use default message
            $this->logger->error('Failed to parse API error response', [
                'response_body' => $responseBody,
                'exception' => $e->getMessage()
            ]);
            return "HTTP Error {$statusCode} - Unable to parse response";
        }

        switch ($statusCode) {
            case 401:
                return 'Unauthorized - Invalid API key';
            case 403:
                return 'Forbidden - API key does not have required permissions';
            case 404:
                return 'Not Found - API endpoint not found';
            case 429:
                return 'Too Many Requests - Rate limit exceeded';
            case 500:
                return 'Internal Server Error';
            default:
                return "HTTP Error {$statusCode}";
        }
    }

    // ==========================================
    // BILLING & SUBSCRIPTION METHODS
    // ==========================================

    /**
     * Get available subscription plans (no auth required)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getBillingPlans(): array
    {
        return $this->makeApiRequestWithoutAuth(self::API_ENDPOINTS['billing_plans'], 'GET');
    }

    /**
     * Get current subscription details
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCurrentSubscription(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['billing_subscription'], 'GET');
    }

    /**
     * Get billing history
     *
     * @return array
     * @throws LocalizedException
     */
    public function getBillingHistory(): array
    {
        return $this->makeApiRequestWithApiKey(self::API_ENDPOINTS['billing_history'], 'GET');
    }

    /**
     * Get billing configuration (no auth required)
     *
     * @return array
     * @throws LocalizedException
     */
    public function getBillingConfig(): array
    {
        return $this->makeApiRequestWithoutAuth(self::API_ENDPOINTS['billing_config'], 'GET');
    }
}
