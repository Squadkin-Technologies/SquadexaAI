<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Service\AiFieldMappingService;
use Psr\Log\LoggerInterface;

/**
 * Controller to get mapped AI product data for form pre-filling
 */
class GetMappedData extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
{
    public const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::squadkin_squadexaai_aiproduct';
    
    /**
     * Public actions that don't require secret key validation
     * @var array
     */
    protected $_publicActions = ['getMappedData'];

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var AiFieldMappingService
     */
    private $mappingService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiFieldMappingService $mappingService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AiProductRepositoryInterface $aiProductRepository,
        AiFieldMappingService $mappingService,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->aiProductRepository = $aiProductRepository;
        $this->mappingService = $mappingService;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if ($request->isXmlHttpRequest() || $request->getParam('isAjax')) {
            return true;
        }
        return null;
    }
    
    /**
     * Override to skip secret key validation for AJAX requests
     *
     * @return bool
     */
    public function _processUrlKeys()
    {
        $request = $this->getRequest();
        
        // Skip secret key validation for AJAX requests
        if ($request->isXmlHttpRequest() || $request->getParam('isAjax')) {
            // Only validate form key for POST requests
            if ($request->isPost()) {
                $_isValidFormKey = $this->_formKeyValidator->validate($request);
                if (!$_isValidFormKey) {
                    if ($request->getQuery('isAjax', false) || $request->getQuery('ajax', false)) {
                        $this->getResponse()->representJson(
                            $this->_objectManager->get(
                                \Magento\Framework\Json\Helper\Data::class
                            )->jsonEncode(
                                ['error' => true, 'message' => __('Invalid Form Key. Please refresh the page.')]
                            )
                        );
                    }
                    return false;
                }
            }
            return true;
        }
        
        // Use parent validation for non-AJAX requests
        return parent::_processUrlKeys();
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $aiProductId = (int)$this->getRequest()->getParam('ai_data');
            $productType = $this->getRequest()->getParam('type', 'simple');

            $this->logger->info('GetMappedData controller called', [
                'ai_product_id' => $aiProductId,
                'product_type' => $productType
            ]);

            if (!$aiProductId) {
                throw new \Exception(__('AI Product ID is required'));
            }

            // Map AI product to Magento product data
            $mappedData = $this->mappingService->mapAiProductToMagento(
                $aiProductId,
                $productType,
                null, // Use default attribute set
                null // mapping_id not used anymore, using system config
            );

            $this->logger->info('Mapped data prepared', [
                'mapped_fields_count' => count($mappedData),
                'mapped_fields' => array_keys($mappedData)
            ]);

            $result->setHttpResponseCode(200);
            return $result->setData([
                'success' => true,
                'data' => $mappedData
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if ($errorMessage instanceof \Magento\Framework\Phrase) {
                $errorMessage = (string)$errorMessage;
            }
            
            $this->logger->error('Error getting mapped AI data: ' . $errorMessage, [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_params' => $this->getRequest()->getParams()
            ]);

            $result->setHttpResponseCode(500);
            return $result->setData([
                'success' => false,
                'message' => $errorMessage
            ]);
        }
    }
}
