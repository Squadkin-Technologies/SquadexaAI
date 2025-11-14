<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\AiProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Service\AiFieldMappingService;
use Psr\Log\LoggerInterface;

/**
 * Create Magento Product from AI Product
 * Returns mapped data for product creation
 */
class CreateMagentoProduct extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::AiProduct';

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
     * Execute action
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $aiProductId = (int)$this->getRequest()->getParam('aiproduct_id');
            $productType = $this->getRequest()->getParam('product_type', 'simple');
            $attributeSetId = $this->getRequest()->getParam('attribute_set_id');
            $mappingId = $this->getRequest()->getParam('mapping_id');

            if (!$aiProductId) {
                throw new \Exception(__('AI Product ID is required'));
            }

            // Map AI product to Magento product data
            $mappedData = $this->mappingService->mapAiProductToMagento(
                $aiProductId,
                $productType,
                $attributeSetId ? (int)$attributeSetId : null,
                $mappingId ? (int)$mappingId : null
            );

            // Build redirect URL to Magento product creation page with pre-filled data
            $redirectUrl = $this->_url->getUrl(
                'catalog/product/new',
                [
                    'type' => $productType,
                    'set' => $attributeSetId,
                    'ai_data' => base64_encode(json_encode($mappedData))
                ]
            );

            return $result->setData([
                'success' => true,
                'redirect_url' => $redirectUrl,
                'mapped_data' => $mappedData
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error creating Magento product from AI: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

