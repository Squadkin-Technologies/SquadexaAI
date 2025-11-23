<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Auth;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Squadkin\SquadexaAI\Service\SquadexaApiService;

class ValidateApiKey extends Action
{
    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @param Context $context
     * @param SquadexaApiService $apiService
     */
    public function __construct(
        Context $context,
        SquadexaApiService $apiService
    ) {
        $this->apiService = $apiService;
        parent::__construct($context);
    }

    /**
     * Validate API key
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        try {
            $apiKey = $this->getRequest()->getParam('api_key');
            
            if (empty($apiKey)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('API key is required.')
                ]);
            }
            
            // Temporarily set the API key for validation
            $originalApiKey = $this->apiService->getApiKey();
            
            // Create a temporary service instance with the new API key
            $tempApiService = $this->_objectManager->create(
                SquadexaApiService::class,
                [
                    'scopeConfig' => $this->_objectManager->get(
                        \Magento\Framework\App\Config\ScopeConfigInterface::class
                    ),
                    'curl' => $this->_objectManager->get(\Magento\Framework\HTTP\Client\Curl::class),
                    'jsonSerializer' => $this->_objectManager
                        ->get(\Magento\Framework\Serialize\Serializer\Json::class),
                    'logger' => $this->_objectManager->get(\Psr\Log\LoggerInterface::class)
                ]
            );
            
            // Override the API key for validation
            $reflection = new \ReflectionClass($tempApiService);
            $scopeConfigProperty = $reflection->getProperty('scopeConfig');
            $scopeConfigProperty->setAccessible(true);
            $scopeConfig = $scopeConfigProperty->getValue($tempApiService);
            
            // Mock the getValue method to return our test API key
            $mockScopeConfig = new class($scopeConfig, $apiKey) {
                /**
                 * @var \Magento\Framework\App\Config\ScopeConfigInterface
                 */
                private $originalScopeConfig;
                
                /**
                 * @var string
                 */
                private $testApiKey;
                
                /**
                 * @param \Magento\Framework\App\Config\ScopeConfigInterface $originalScopeConfig
                 * @param string $testApiKey
                 */
                public function __construct($originalScopeConfig, $testApiKey)
                {
                    $this->originalScopeConfig = $originalScopeConfig;
                    $this->testApiKey = $testApiKey;
                }
                
                /**
                 * Get config value
                 *
                 * @param string $path
                 * @param string $scopeType
                 * @param string|null $scopeCode
                 * @return string|null
                 */
                public function getValue(
                    $path,
                    $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $scopeCode = null
                ) {
                    if ($path === 'squadexaiproductcreator/authentication/api_key') {
                        return $this->testApiKey;
                    }
                    return $this->originalScopeConfig->getValue($path, $scopeType, $scopeCode);
                }
            };
            
            $scopeConfigProperty->setValue($tempApiService, $mockScopeConfig);
            
            // Validate the API key
            $isValid = $tempApiService->validateApiKey();
            
            if ($isValid) {
                $accountInfo = $tempApiService->getAccountInformation();
                return $resultJson->setData([
                    'success' => true,
                    'message' => __('API key is valid.'),
                    'account_info' => $accountInfo
                ]);
            } else {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('API key is invalid or expired.')
                ]);
            }
            
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while validating the API key: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::config_squadkin_squadexaai');
    }
}
