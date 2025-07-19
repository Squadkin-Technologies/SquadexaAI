<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface;
use Squadkin\AIAutoProductBuilder\Api\AiProductRepositoryInterface;

class MagentoProductCreator
{
    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var CustomAttributeProcessor
     */
    private $customAttributeProcessor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param CustomAttributeProcessor $customAttributeProcessor
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        AiProductRepositoryInterface $aiProductRepository,
        CustomAttributeProcessor $customAttributeProcessor,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->aiProductRepository = $aiProductRepository;
        $this->customAttributeProcessor = $customAttributeProcessor;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Create Magento product from AI product data
     *
     * @param AiProductInterface $aiProduct
     * @return ProductInterface
     * @throws LocalizedException
     */
    public function createMagentoProduct(AiProductInterface $aiProduct): ProductInterface
    {
        try {
            // Check if product already exists
            if ($aiProduct->getMagentoProductId()) {
                try {
                    $existingProduct = $this->productRepository->getById($aiProduct->getMagentoProductId());
                    return $this->updateExistingProduct($existingProduct, $aiProduct);
                } catch (\Exception $e) {
                    // Product doesn't exist, create new one
                }
            }

            // Create new product
            /** @var ProductInterface $product */
            $product = $this->productFactory->create();
            
            // Set basic product data
            $this->setBasicProductData($product, $aiProduct);
            
            // Apply custom attributes
            $this->customAttributeProcessor->applyCustomAttributesToProduct($product, $aiProduct);
            
            // Set required Magento attributes
            $this->setRequiredMagentoAttributes($product, $aiProduct);
            
            // Save the product
            $savedProduct = $this->productRepository->save($product);
            
            // Update AI product with Magento product ID
            $aiProduct->setMagentoProductId($savedProduct->getId());
            $aiProduct->setIsCreatedInMagento(true);
            $this->aiProductRepository->save($aiProduct);
            
            $this->logger->info('Created Magento product with ID: ' . $savedProduct->getId() . ' from AI product: ' . $aiProduct->getSku());
            
            return $savedProduct;
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating Magento product: ' . $e->getMessage());
            throw new LocalizedException(__('Could not create Magento product: %1', $e->getMessage()));
        }
    }

    /**
     * Update existing Magento product
     *
     * @param ProductInterface $product
     * @param AiProductInterface $aiProduct
     * @return ProductInterface
     * @throws LocalizedException
     */
    private function updateExistingProduct(ProductInterface $product, AiProductInterface $aiProduct): ProductInterface
    {
        try {
            // Update basic product data
            $this->setBasicProductData($product, $aiProduct);
            
            // Apply custom attributes
            $this->customAttributeProcessor->applyCustomAttributesToProduct($product, $aiProduct);
            
            // Save the updated product
            $savedProduct = $this->productRepository->save($product);
            
            $this->logger->info('Updated Magento product with ID: ' . $savedProduct->getId() . ' from AI product: ' . $aiProduct->getSku());
            
            return $savedProduct;
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating Magento product: ' . $e->getMessage());
            throw new LocalizedException(__('Could not update Magento product: %1', $e->getMessage()));
        }
    }

    /**
     * Set basic product data
     *
     * @param ProductInterface $product
     * @param AiProductInterface $aiProduct
     * @return void
     */
    private function setBasicProductData(ProductInterface $product, AiProductInterface $aiProduct): void
    {
        $product->setSku($aiProduct->getSku());
        $product->setName($aiProduct->getName());
        $product->setPrice($aiProduct->getPrice());
        $product->setWeight($aiProduct->getWeight());
        
        // Set descriptions
        if ($aiProduct->getDescription()) {
            $product->setCustomAttribute('description', $aiProduct->getDescription());
        }
        if ($aiProduct->getShortDescription()) {
            $product->setCustomAttribute('short_description', $aiProduct->getShortDescription());
        }
        
        // Set special price if available
        if ($aiProduct->getSpecialPrice()) {
            $product->setCustomAttribute('special_price', $aiProduct->getSpecialPrice());
        }
        
        // Set SEO attributes
        if ($aiProduct->getMetaTitle()) {
            $product->setCustomAttribute('meta_title', $aiProduct->getMetaTitle());
        }
        if ($aiProduct->getMetaDescription()) {
            $product->setCustomAttribute('meta_description', $aiProduct->getMetaDescription());
        }
        if ($aiProduct->getMetaKeywords()) {
            $product->setCustomAttribute('meta_keyword', $aiProduct->getMetaKeywords());
        }
        if ($aiProduct->getUrlKey()) {
            $product->setCustomAttribute('url_key', $aiProduct->getUrlKey());
        }
    }

    /**
     * Set required Magento attributes
     *
     * @param ProductInterface $product
     * @param AiProductInterface $aiProduct
     * @return void
     */
    private function setRequiredMagentoAttributes(ProductInterface $product, AiProductInterface $aiProduct): void
    {
        // Set product type
        $productType = $this->mapProductType($aiProduct->getType());
        $product->setTypeId($productType);
        
        // Set attribute set (default to 4 which is usually the default attribute set)
        $product->setAttributeSetId(4);
        
        // Set status
        $status = $this->mapProductStatus($aiProduct->getStatus());
        $product->setStatus($status);
        
        // Set visibility
        $visibility = $this->mapProductVisibility($aiProduct->getVisibility());
        $product->setVisibility($visibility);
        
        // Set website IDs
        $websiteIds = [$this->storeManager->getDefaultStoreView()->getWebsiteId()];
        $product->setWebsiteIds($websiteIds);
        
        // Set stock data
        $stockData = [
            'use_config_manage_stock' => 1,
            'qty' => $aiProduct->getQty() ?: 0,
            'is_in_stock' => ($aiProduct->getQty() ?: 0) > 0 ? 1 : 0
        ];
        $product->setStockData($stockData);
    }

    /**
     * Map AI product type to Magento product type
     *
     * @param string|null $aiProductType
     * @return string
     */
    private function mapProductType(?string $aiProductType): string
    {
        $typeMap = [
            'simple' => Type::TYPE_SIMPLE,
            'configurable' => Type::TYPE_CONFIGURABLE,
            'grouped' => Type::TYPE_GROUPED,
            'virtual' => Type::TYPE_VIRTUAL,
            'bundle' => Type::TYPE_BUNDLE,
            'downloadable' => 'downloadable'
        ];
        
        return $typeMap[strtolower($aiProductType ?? 'simple')] ?? Type::TYPE_SIMPLE;
    }

    /**
     * Map AI product status to Magento product status
     *
     * @param string|null $aiProductStatus
     * @return int
     */
    private function mapProductStatus(?string $aiProductStatus): int
    {
        $statusMap = [
            'enabled' => Status::STATUS_ENABLED,
            'disabled' => Status::STATUS_DISABLED,
            'active' => Status::STATUS_ENABLED,
            'inactive' => Status::STATUS_DISABLED
        ];
        
        return $statusMap[strtolower($aiProductStatus ?? 'enabled')] ?? Status::STATUS_ENABLED;
    }

    /**
     * Map AI product visibility to Magento product visibility
     *
     * @param string|null $aiProductVisibility
     * @return int
     */
    private function mapProductVisibility(?string $aiProductVisibility): int
    {
        $visibilityMap = [
            'not visible individually' => Visibility::VISIBILITY_NOT_VISIBLE,
            'catalog' => Visibility::VISIBILITY_IN_CATALOG,
            'search' => Visibility::VISIBILITY_IN_SEARCH,
            'catalog, search' => Visibility::VISIBILITY_BOTH
        ];
        
        return $visibilityMap[strtolower($aiProductVisibility ?? 'catalog, search')] ?? Visibility::VISIBILITY_BOTH;
    }

    /**
     * Create multiple Magento products from AI products
     *
     * @param array $aiProductIds
     * @return array
     */
    public function createMultipleMagentoProducts(array $aiProductIds): array
    {
        $results = [];
        
        foreach ($aiProductIds as $aiProductId) {
            try {
                $aiProduct = $this->aiProductRepository->get($aiProductId);
                $magentoProduct = $this->createMagentoProduct($aiProduct);
                $results[] = [
                    'ai_product_id' => $aiProductId,
                    'magento_product_id' => $magentoProduct->getId(),
                    'sku' => $magentoProduct->getSku(),
                    'status' => 'success'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'ai_product_id' => $aiProductId,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }
        
        return $results;
    }
} 