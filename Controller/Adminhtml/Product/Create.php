<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Psr\Log\LoggerInterface;

class Create extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductInterfaceFactory
     */
    protected $productFactory;

    /**
     * @var AiProductRepositoryInterface
     */
    protected $aiProductRepository;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    protected $generatedCsvRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        AiProductRepositoryInterface $aiProductRepository,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->aiProductRepository = $aiProductRepository;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Create Magento product from AI generated data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $csvId = (int)$this->getRequest()->getParam('csv_id');
            $productData = $this->getRequest()->getParam('product');
            
            if (!$csvId || empty($productData)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Invalid request data.')
                ]);
            }

            // Get AI products for this CSV
            $aiProducts = $this->getAiProductsByCsvId($csvId);
            
            if (empty($aiProducts)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('No AI products found.')
                ]);
            }

            $createdProducts = [];
            $errors = [];

            foreach ($aiProducts as $aiProduct) {
                try {
                    // Get product data from form (user might have edited)
                    $productFormData = $productData[$aiProduct->getAiproductId()] ?? [];
                    
                    if (empty($productFormData)) {
                        continue; // Skip if not in form data
                    }

                    // Create Magento product
                    $magentoProduct = $this->createMagentoProduct($aiProduct, $productFormData);
                    
                    // Update AI product record
                    $aiProduct->setIsCreatedInMagento(true);
                    $aiProduct->setMagentoProductId((int)$magentoProduct->getId());
                    $this->aiProductRepository->save($aiProduct);
                    
                    $createdProducts[] = [
                        'sku' => $magentoProduct->getSku(),
                        'name' => $magentoProduct->getName(),
                        'id' => $magentoProduct->getId()
                    ];
                    
                } catch (\Exception $e) {
                    $this->logger->error('Error creating product: ' . $e->getMessage());
                    $errors[] = __('Failed to create product %1: %2', 
                        $aiProduct->getSku(), 
                        $e->getMessage()
                    );
                }
            }

            // Update Generated CSV status
            if (!empty($createdProducts)) {
                try {
                    $generatedCsv = $this->generatedCsvRepository->get($csvId);
                    $generatedCsv->setImportStatus('completed');
                    $generatedCsv->setImportedProductsCount(count($createdProducts));
                    $generatedCsv->setImportedAt(date('Y-m-d H:i:s'));
                    $this->generatedCsvRepository->save($generatedCsv);
                } catch (\Exception $e) {
                    $this->logger->error('Error updating CSV status: ' . $e->getMessage());
                }
            }

            $message = __('%1 product(s) created successfully.', count($createdProducts));
            if (!empty($errors)) {
                $message .= ' ' . __('Errors: %1', implode(', ', $errors));
            }

            return $result->setData([
                'success' => !empty($createdProducts),
                'message' => $message,
                'created_products' => $createdProducts,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error in product creation: ' . $e->getMessage());
            
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Get AI products by CSV ID
     *
     * @param int $csvId
     * @return array
     */
    private function getAiProductsByCsvId(int $csvId): array
    {
        $products = [];
        $searchResults = $this->aiProductRepository->getList();
        
        if ($searchResults && $searchResults->getItems()) {
            foreach ($searchResults->getItems() as $aiProduct) {
                if ($aiProduct->getGeneratedcsvId() == $csvId) {
                    $products[] = $aiProduct;
                }
            }
        }
        
        return $products;
    }

    /**
     * Create Magento product from AI product data
     *
     * @param \Squadkin\SquadexaAI\Api\Data\AiProductInterface $aiProduct
     * @param array $formData
     * @return Product
     * @throws \Exception
     */
    private function createMagentoProduct($aiProduct, array $formData): Product
    {
        /** @var Product $product */
        $product = $this->productFactory->create();
        
        // Set required fields
        $product->setSku($formData['sku'] ?? $aiProduct->getSku());
        $product->setName($formData['name'] ?? $aiProduct->getName());
        $product->setAttributeSetId(4); // Default attribute set
        $product->setTypeId(Product::TYPE_SIMPLE);
        $product->setWebsiteIds([$this->storeManager->getStore()->getWebsiteId()]);
        
        // Set status and visibility
        $status = isset($formData['status']) ? (int)$formData['status'] : Status::STATUS_ENABLED;
        $product->setStatus($status);
        
        $visibility = isset($formData['visibility']) ? (int)$formData['visibility'] : Visibility::VISIBILITY_BOTH;
        $product->setVisibility($visibility);
        
        // Set pricing
        $product->setPrice((float)($formData['price'] ?? $aiProduct->getPrice()));
        
        if (!empty($formData['special_price'])) {
            $product->setSpecialPrice((float)$formData['special_price']);
        }
        
        // Set descriptions
        $product->setDescription($formData['description'] ?? $aiProduct->getDescription());
        $product->setShortDescription($formData['short_description'] ?? $aiProduct->getShortDescription());
        
        // Set inventory
        $product->setStockData([
            'use_config_manage_stock' => 1,
            'manage_stock' => 1,
            'is_in_stock' => 1,
            'qty' => (int)($formData['qty'] ?? $aiProduct->getQty() ?? 100)
        ]);
        
        // Set weight
        $weight = (float)($formData['weight'] ?? $aiProduct->getWeight() ?? 1);
        $product->setWeight($weight);
        
        // Set SEO fields
        if (!empty($formData['meta_title'])) {
            $product->setMetaTitle($formData['meta_title']);
        }
        if (!empty($formData['meta_description'])) {
            $product->setMetaDescription($formData['meta_description']);
        }
        if (!empty($formData['meta_keywords'])) {
            $product->setMetaKeyword($formData['meta_keywords']);
        }
        if (!empty($formData['url_key'])) {
            $product->setUrlKey($formData['url_key']);
        }
        
        // Set tax class
        if (!empty($formData['tax_class_id'])) {
            $product->setTaxClassId((int)$formData['tax_class_id']);
        }
        
        // Save product
        return $this->productRepository->save($product);
    }

    /**
     * Check if user has permission
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv');
    }
}

