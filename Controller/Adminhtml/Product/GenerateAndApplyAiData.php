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
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Squadkin\SquadexaAI\Helper\FieldMapping as FieldMappingHelper;
use Psr\Log\LoggerInterface;

/**
 * Generate AI data and return mapped data for form (no database save)
 * This is a new flow specifically for product edit page
 */
class GenerateAndApplyAiData extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var FieldMappingHelper
     */
    private $fieldMappingHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SquadexaApiService $apiService
     * @param FieldMappingHelper $fieldMappingHelper
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SquadexaApiService $apiService,
        FieldMappingHelper $fieldMappingHelper,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiService = $apiService;
        $this->fieldMappingHelper = $fieldMappingHelper;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Execute - Generate AI data and return mapped data for form
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $productName = $this->getRequest()->getParam('product_name');
            $primaryKeywords = $this->getRequest()->getParam('primary_keywords');
            $secondaryKeywords = $this->getRequest()->getParam('secondary_keywords', '');
            $includePricing = (bool)$this->getRequest()->getParam('include_pricing', false);
            $productId = (int)$this->getRequest()->getParam('product_id', 0);

            // Validate required fields
            if (empty($productName) || empty($primaryKeywords)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product name and primary keywords are required.')
                ]);
            }

            // Get product type from existing product if editing
            $productType = 'simple';
            if ($productId > 0) {
                try {
                    $product = $this->productRepository->getById($productId);
                    $productType = $product->getTypeId();
                } catch (\Exception $e) {
                    $this->logger->warning('Could not get product type, using default', [
                        'product_id' => $productId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Prepare data for API
            $productData = [
                'product_name' => $productName,
                'primary_keywords' => explode(',', $primaryKeywords),
                'secondary_keywords' => !empty($secondaryKeywords) ? explode(',', $secondaryKeywords) : [],
                'include_pricing' => $includePricing
            ];

            // Call API to generate product
            $apiResponse = $this->apiService->generateProduct($productData);

            // Ensure product_name is in response (API might not return it)
            if (!isset($apiResponse['product_name']) || empty($apiResponse['product_name'])) {
                $apiResponse['product_name'] = $productName;
            }

            // Flatten pricing data if it exists (convert nested pricing to flat price field)
            if (isset($apiResponse['pricing']) && is_array($apiResponse['pricing'])) {
                $pricing = $apiResponse['pricing'];
                // Use USD min_price as the main price if available
                if (isset($pricing['USD']['min_price'])) {
                    $apiResponse['price'] = (float)$pricing['USD']['min_price'];
                } elseif (isset($pricing['USD']['max_price'])) {
                    $apiResponse['price'] = (float)$pricing['USD']['max_price'];
                } elseif (isset($pricing['CAD']['min_price'])) {
                    $apiResponse['price'] = (float)$pricing['CAD']['min_price'];
                } elseif (isset($pricing['CAD']['max_price'])) {
                    $apiResponse['price'] = (float)$pricing['CAD']['max_price'];
                }
            }

            // Map AI response data to Magento product attributes using field mapping
            $mappedData = $this->fieldMappingHelper->mapAiDataToMagento($apiResponse);

            $this->logger->info('AI data generated and mapped for product edit', [
                'product_id' => $productId,
                'product_type' => $productType,
                'mapped_fields_count' => count($mappedData),
                'mapped_fields' => array_keys($mappedData)
            ]);

            return $result->setData([
                'success' => true,
                'message' => __('AI data generated successfully!'),
                'mapped_data' => $mappedData,
                'raw_data' => $apiResponse // Include raw data for reference
            ]);

        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Generate and Apply AI Data Error', [
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
