<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Service\AiFieldMappingService;
use Psr\Log\LoggerInterface;

/**
 * Create Magento Product from AI Product
 * Returns mapped data for product creation
 */
class CreateMagentoProduct extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::squadkin_squadexaai_aiproduct';

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
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiFieldMappingService $mappingService
     * @param ProductInterfaceFactory $productFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AiProductRepositoryInterface $aiProductRepository,
        AiFieldMappingService $mappingService,
        ProductInterfaceFactory $productFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->aiProductRepository = $aiProductRepository;
        $this->mappingService = $mappingService;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
    }

    /**
     * Create exception in case CSRF validation failed.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // Allow AJAX requests without CSRF validation
        if ($request->isXmlHttpRequest() || $request->getParam('isAjax')) {
            return true;
        }
        return null; // Use default validation for non-AJAX requests
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
            $this->logger->info('CreateMagentoProduct controller called', [
                'request_params' => $this->getRequest()->getParams()
            ]);
            
            $aiProductId = (int)$this->getRequest()->getParam('aiproduct_id');
            $productType = $this->getRequest()->getParam('product_type', 'simple');

            $this->logger->info('CreateMagentoProduct params', [
                'ai_product_id' => $aiProductId,
                'product_type' => $productType
            ]);

            if (!$aiProductId) {
                throw new \Exception(__('AI Product ID is required'));
            }

            // Map AI product to Magento product data using system configuration mapping
            // Magento will use default attribute set for the product type
            $this->logger->info('Calling mapAiProductToMagento', [
                'ai_product_id' => $aiProductId,
                'product_type' => $productType
            ]);
            
            $mappedData = $this->mappingService->mapAiProductToMagento(
                $aiProductId,
                $productType,
                null, // Use default attribute set
                null // mapping_id not used anymore, using system config
            );

            $this->logger->info('Mapped data received', [
                'mapped_fields_count' => count($mappedData),
                'mapped_fields' => array_keys($mappedData)
            ]);

            // Get default attribute set ID for the product type
            $defaultAttributeSetId = (int)$this->productFactory->create()->getDefaultAttributeSetId();
            $this->logger->info('Default attribute set ID', ['attribute_set_id' => $defaultAttributeSetId]);

            // Build redirect URL to Magento product creation page with pre-filled data
            // Format: /admin/catalog/product/new/set/{attributeSetId}/type/{productType}/key/{secretKey}/
            // Note: ai_data contains the AI product ID, which will be used to fetch mapped data via AJAX
            $baseUrl = $this->_url->getUrl(
                'catalog/product/new',
                [
                    'set' => $defaultAttributeSetId,
                    'type' => $productType
                ]
            );
            
            // Add ai_data as query parameter explicitly
            $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
            $redirectUrl = $baseUrl . $separator . 'ai_data=' . urlencode((string)$aiProductId);

            $this->logger->info('Redirect URL generated', ['redirect_url' => $redirectUrl]);

            $result->setHttpResponseCode(200);
            return $result->setData([
                'success' => true,
                'redirect_url' => $redirectUrl
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            // Convert Phrase to string if needed
            if ($errorMessage instanceof \Magento\Framework\Phrase) {
                $errorMessage = (string)$errorMessage;
            }
            
            $this->logger->error('Error creating Magento product from AI: ' . $errorMessage, [
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
