<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Squadkin\SquadexaAI\Service\AiFieldMappingService;
use Psr\Log\LoggerInterface;

/**
 * Apply AI data to existing product
 */
class ApplyAiData extends Action
{
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

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
     * @param ProductRepositoryInterface $productRepository
     * @param AiFieldMappingService $mappingService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepositoryInterface $productRepository,
        AiFieldMappingService $mappingService,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->mappingService = $mappingService;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $productId = (int)$this->getRequest()->getParam('product_id');
            $aiProductId = (int)$this->getRequest()->getParam('ai_product_id');

            if (!$productId || !$aiProductId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product ID and AI Product ID are required.')
                ]);
            }

            // Get product
            $product = $this->productRepository->getById($productId);

            // Map and apply AI data
            $mappedData = $this->mappingService->mapAiProductToMagentoProduct($aiProductId, $product->getTypeId());

            // Apply mapped data to product
            foreach ($mappedData as $attributeCode => $value) {
                if ($value !== null && $value !== '') {
                    $product->setData($attributeCode, $value);
                }
            }

            // Save product
            $this->productRepository->save($product);

            return $result->setData([
                'success' => true,
                'message' => __('AI data has been applied to the product successfully!')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Apply AI Data Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $result->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}

