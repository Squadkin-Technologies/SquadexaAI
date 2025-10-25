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

class TestConnection extends Action
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
     * Test API connection
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        try {
            // Test health check first (no auth required)
            $healthResult = $this->apiService->checkHealth();
            
            // Test API key validation
            $isValid = $this->apiService->validateApiKey();
            
            if ($isValid) {
                $accountInfo = $this->apiService->getAccountInformation();
                return $resultJson->setData([
                    'success' => true,
                    'message' => __('API connection successful!'),
                    'health_check' => $healthResult,
                    'api_key_valid' => true,
                    'account_info' => $accountInfo
                ]);
            } else {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('API connection successful but API key is invalid.'),
                    'health_check' => $healthResult,
                    'api_key_valid' => false
                ]);
            }
            
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'localized'
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Connection test failed: %1', $e->getMessage()),
                'error_type' => 'general'
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
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::squadkin_squadexaai_auth_validate');
    }
}
