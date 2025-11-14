<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Service\AiFieldMappingService;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;

/**
 * Update Magento Product from AI Data
 */
class UpdateFromAi extends Action
{
    /**
     * Authorization level of a basic admin session
     */
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
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var AiFieldMappingService
     */
    private $mappingService;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiFieldMappingService $mappingService
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepositoryInterface $productRepository,
        AiProductRepositoryInterface $aiProductRepository,
        AiFieldMappingService $mappingService,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->aiProductRepository = $aiProductRepository;
        $this->mappingService = $mappingService;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Get AI data for popup (read-only)
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $productId = (int)$this->getRequest()->getParam('id');
            $aiProductId = (int)$this->getRequest()->getParam('ai_product_id');
            $action = $this->getRequest()->getParam('action', 'get_data');

            if ($action === 'get_data') {
                // Return AI product data for popup display
                if (!$aiProductId) {
                    throw new \Exception(__('AI Product ID is required'));
                }

                $aiProduct = $this->aiProductRepository->getById($aiProductId);
                
                return $result->setData([
                    'success' => true,
                    'ai_product' => [
                        'aiproduct_id' => $aiProduct->getAiproductId(),
                        'product_name' => $aiProduct->getProductName(),
                        'meta_title' => $aiProduct->getMetaTitle(),
                        'meta_description' => $aiProduct->getMetaDescription(),
                        'short_description' => $aiProduct->getShortDescription(),
                        'description' => $aiProduct->getDescription(),
                        'key_features' => $aiProduct->getKeyFeatures(),
                        'how_to_use' => $aiProduct->getHowToUse(),
                        'ingredients' => $aiProduct->getIngredients(),
                        'upc' => $aiProduct->getUpc(),
                        'keywords' => $aiProduct->getKeywords(),
                        'pricing_usd_min' => $aiProduct->getPricingUsdMin(),
                        'pricing_usd_max' => $aiProduct->getPricingUsdMax(),
                    ]
                ]);
            } elseif ($action === 'update') {
                // Update product with AI data
                if (!$productId || !$aiProductId) {
                    throw new \Exception(__('Product ID and AI Product ID are required'));
                }

                // Get AI data from request (may be edited in popup)
                $aiData = $this->getRequest()->getParam('ai_data', []);
                if (is_string($aiData)) {
                    $aiData = $this->jsonSerializer->unserialize($aiData);
                }

                $mappingId = $this->getRequest()->getParam('mapping_id');

                // Update product
                $product = $this->mappingService->updateProductFromAi(
                    $productId,
                    $aiProductId,
                    $aiData,
                    $mappingId ? (int)$mappingId : null
                );

                // Save product
                $this->productRepository->save($product);

                return $result->setData([
                    'success' => true,
                    'message' => __('Product has been updated successfully from AI data.')
                ]);
            }

            throw new \Exception(__('Invalid action'));
        } catch (\Exception $e) {
            $this->logger->error('Error updating product from AI: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

