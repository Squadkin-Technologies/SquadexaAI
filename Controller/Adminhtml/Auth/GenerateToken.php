<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Auth;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class GenerateToken extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var SquadexaApiService
     */
    protected $apiService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param SquadexaApiService $apiService
     * @param LoggerInterface $logger
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        SquadexaApiService $apiService,
        LoggerInterface $logger,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->apiService = $apiService;
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
    }

    /**
     * Generate access token
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getParam('password');

        if (empty($username) || empty($password)) {
            return $result->setData([
                'success' => false,
                'message' => __('Username and password are required.')
            ]);
        }

        try {
            $this->logger->info('SquadexaAI: Starting 2-step authentication process', [
                'username' => $username
            ]);

            // Step 1: Login to get temporary access token
            $this->logger->info('SquadexaAI: Step 1 - Login to get temporary access token');
            $loginData = $this->apiService->login($username, $password);
            
            if (!isset($loginData['access_token']) || empty($loginData['access_token'])) {
                $this->logger->warning('SquadexaAI: Login response missing access token', [
                    'username' => $username,
                    'response' => $loginData
                ]);

                return $result->setData([
                    'success' => false,
                    'message' => __('Login successful but no access token received.')
                ]);
            }

            $accessToken = $loginData['access_token'];
            $this->logger->info('SquadexaAI: Step 1 completed - Access token received', [
                'username' => $username,
                'token_length' => strlen($accessToken)
            ]);

            // Step 2: Generate permanent API key using access token
            $this->logger->info('SquadexaAI: Step 2 - Generate permanent API key');
            $apiKeyData = $this->apiService->generateApiKey($accessToken);
            
            if (isset($apiKeyData['api_key']) && !empty($apiKeyData['api_key'])) {
                $this->logger->info('SquadexaAI: Step 2 completed - API key generated successfully', [
                    'username' => $username,
                    'api_key_length' => strlen($apiKeyData['api_key'])
                ]);

                // Save ONLY the API key encrypted (not the access token as it expires in 30 minutes)
                $apiKeyCreated = date('Y-m-d H:i:s');
                $encryptedApiKey = $this->encryptor->encrypt($apiKeyData['api_key']);
                $this->configWriter->save('squadexaiproductcreator/authentication/api_key', $encryptedApiKey);
                $this->configWriter->save('squadexaiproductcreator/authentication/api_key_created', $apiKeyCreated);
                
                $this->logger->info('SquadexaAI: API key saved to database (access token NOT saved as it expires)', [
                    'api_key_length' => strlen($apiKeyData['api_key']),
                    'api_key_created' => $apiKeyCreated
                ]);

                return $result->setData([
                    'success' => true,
                    'message' => __(
                        'API key generated successfully! Your permanent API key has been saved and is ready to use.'
                    ),
                    'api_key' => $apiKeyData['api_key'],
                    'api_key_created' => $apiKeyCreated
                ]);
            } else {
                $this->logger->warning('SquadexaAI: API key generation response missing api_key', [
                    'username' => $username,
                    'response' => $apiKeyData
                ]);

                return $result->setData([
                    'success' => false,
                    'message' => __('Access token received but API key generation failed.')
                ]);
            }

        } catch (LocalizedException $e) {
            $this->logger->error('SquadexaAI: Login failed - LocalizedException', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            $message = $e->getMessage();

            // Surface email-not-verified error with helpful guidance
            if (stripos($message, 'EMAIL_NOT_VERIFIED') !== false ||
                stripos($message, 'not verified') !== false) {
                $message = __(
                    'Your email address is not verified. Please check your inbox for a verification '
                    . 'email from Squadexa AI and click the link to verify your account before logging in.'
                );
            }

            return $result->setData([
                'success' => false,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI: Login failed - General Exception', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $result->setData([
                'success' => false,
                'message' => __('An unexpected error occurred during login: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user is allowed to access this action
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::squadkin_squadexaai_auth_validate');
    }
}
