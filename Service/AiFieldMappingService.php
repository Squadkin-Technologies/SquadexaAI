<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Service;

use Squadkin\SquadexaAI\Api\FieldMappingRepositoryInterface;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * AI Field Mapping Service
 * Maps AI-generated product data to Magento product attributes
 */
class AiFieldMappingService
{
    /**
     * @var FieldMappingRepositoryInterface
     */
    private $mappingRepository;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FieldMappingRepositoryInterface $mappingRepository
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        FieldMappingRepositoryInterface $mappingRepository,
        AiProductRepositoryInterface $aiProductRepository,
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        AttributeRepositoryInterface $attributeRepository,
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->aiProductRepository = $aiProductRepository;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->attributeRepository = $attributeRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Map AI product data to Magento product
     *
     * @param int $aiProductId
     * @param string $productType
     * @param int|null $attributeSetId
     * @param int|null $mappingId
     * @return array Mapped product data ready for Magento product form
     * @throws LocalizedException
     */
    public function mapAiProductToMagento(
        int $aiProductId,
        string $productType = 'simple',
        ?int $attributeSetId = null,
        ?int $mappingId = null
    ): array {
        try {
            // Get AI Product
            $aiProduct = $this->aiProductRepository->getById($aiProductId);

            // Get Mapping Profile
            $mapping = $this->getMappingProfile($mappingId, $productType, $attributeSetId);
            if (!$mapping) {
                throw new LocalizedException(__('No mapping profile found for product type: %1', $productType));
            }

            // Parse mapping rules
            $mappingRules = $this->jsonSerializer->unserialize($mapping->getMappingRules());

            // Get AI product data
            $aiData = $this->extractAiProductData($aiProduct);

            // Map fields
            $magentoData = $this->applyMapping($aiData, $mappingRules, $productType, $attributeSetId);

            $this->logger->info('AI Field Mapping: Successfully mapped AI product to Magento', [
                'ai_product_id' => $aiProductId,
                'product_type' => $productType,
                'mapping_id' => $mapping->getMappingId()
            ]);

            return $magentoData;
        } catch (\Exception $e) {
            $this->logger->error('AI Field Mapping Error: ' . $e->getMessage(), [
                'ai_product_id' => $aiProductId,
                'exception' => $e
            ]);
            throw new LocalizedException(__('Error mapping AI product: %1', $e->getMessage()), $e);
        }
    }

    /**
     * Update existing Magento product with AI data
     *
     * @param int $productId
     * @param int $aiProductId
     * @param array $aiData Override AI data (optional)
     * @param int|null $mappingId
     * @return ProductInterface
     * @throws LocalizedException
     */
    public function updateProductFromAi(
        int $productId,
        int $aiProductId,
        array $aiData = [],
        ?int $mappingId = null
    ): ProductInterface {
        try {
            // Get existing product
            $product = $this->productRepository->getById($productId);

            // Get AI Product
            $aiProduct = $this->aiProductRepository->getById($aiProductId);

            // Get product type and attribute set from existing product
            $productType = $product->getTypeId();
            $attributeSetId = $product->getAttributeSetId();

            // Get Mapping Profile
            $mapping = $this->getMappingProfile($mappingId, $productType, $attributeSetId);
            if (!$mapping) {
                throw new LocalizedException(__('No mapping profile found'));
            }

            // Parse mapping rules
            $mappingRules = $this->jsonSerializer->unserialize($mapping->getMappingRules());

            // Get AI product data (use provided data or extract from AI product)
            $aiProductData = !empty($aiData) ? $aiData : $this->extractAiProductData($aiProduct);

            // Apply mapping
            $mappedData = $this->applyMapping($aiProductData, $mappingRules, $productType, $attributeSetId);

            // Update product with mapped data
            foreach ($mappedData as $attributeCode => $value) {
                if ($value !== null && $value !== '') {
                    $product->setData($attributeCode, $value);
                }
            }

            $this->logger->info('AI Field Mapping: Successfully updated product from AI', [
                'product_id' => $productId,
                'ai_product_id' => $aiProductId
            ]);

            return $product;
        } catch (\Exception $e) {
            $this->logger->error('AI Field Mapping Update Error: ' . $e->getMessage(), [
                'product_id' => $productId,
                'ai_product_id' => $aiProductId,
                'exception' => $e
            ]);
            throw new LocalizedException(__('Error updating product from AI: %1', $e->getMessage()), $e);
        }
    }

    /**
     * Get mapping profile
     *
     * @param int|null $mappingId
     * @param string $productType
     * @param int|null $attributeSetId
     * @return \Squadkin\SquadexaAI\Api\Data\FieldMappingInterface|null
     */
    private function getMappingProfile(?int $mappingId, string $productType, ?int $attributeSetId = null)
    {
        if ($mappingId) {
            try {
                return $this->mappingRepository->getById($mappingId);
            } catch (\Exception $e) {
                $this->logger->warning('Mapping profile not found by ID: ' . $mappingId);
            }
        }

        // Try to get by product type and attribute set
        $mapping = $this->mappingRepository->getByProductTypeAndAttributeSet($productType, $attributeSetId);
        if ($mapping) {
            return $mapping;
        }

        // Fallback to default
        return $this->mappingRepository->getDefault();
    }

    /**
     * Extract AI product data as array
     *
     * @param \Squadkin\SquadexaAI\Api\Data\AiProductInterface $aiProduct
     * @return array
     */
    private function extractAiProductData($aiProduct): array
    {
        $data = [
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
            'pricing_cad_min' => $aiProduct->getPricingCadMin(),
            'pricing_cad_max' => $aiProduct->getPricingCadMax(),
        ];

        // Parse additional information if available
        if ($aiProduct->getAdditionalInformation()) {
            $additionalData = $this->jsonSerializer->unserialize($aiProduct->getAdditionalInformation());
            if (is_array($additionalData)) {
                $data = array_merge($data, $additionalData);
            }
        }

        // Parse AI response if available
        if ($aiProduct->getAiResponse()) {
            $aiResponse = $this->jsonSerializer->unserialize($aiProduct->getAiResponse());
            if (is_array($aiResponse)) {
                $data = array_merge($data, $aiResponse);
            }
        }

        return $data;
    }

    /**
     * Apply mapping rules to AI data
     *
     * @param array $aiData
     * @param array $mappingRules
     * @param string $productType
     * @param int|null $attributeSetId
     * @return array
     */
    private function applyMapping(array $aiData, array $mappingRules, string $productType, ?int $attributeSetId = null): array
    {
        $magentoData = [
            'type_id' => $productType,
        ];

        if ($attributeSetId) {
            $magentoData['attribute_set_id'] = $attributeSetId;
        }

        // Apply mapping rules
        if (isset($mappingRules['map']) && is_array($mappingRules['map'])) {
            foreach ($mappingRules['map'] as $aiField => $magentoAttribute) {
                $value = $this->getNestedValue($aiData, $aiField);
                if ($value !== null && $value !== '') {
                    $magentoData[$magentoAttribute] = $this->formatValueForAttribute($magentoAttribute, $value);
                }
            }
        }

        // Set default required fields if not mapped
        if (!isset($magentoData['name']) && isset($aiData['product_name'])) {
            $magentoData['name'] = $aiData['product_name'];
        }

        if (!isset($magentoData['sku']) && isset($aiData['upc'])) {
            $magentoData['sku'] = 'AI-' . $aiData['upc'];
        } elseif (!isset($magentoData['sku'])) {
            $magentoData['sku'] = 'AI-' . time() . '-' . rand(1000, 9999);
        }

        return $magentoData;
    }

    /**
     * Get nested value from array using dot notation
     *
     * @param array $data
     * @param string $path
     * @return mixed|null
     */
    private function getNestedValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Format value for Magento attribute
     *
     * @param string $attributeCode
     * @param mixed $value
     * @return mixed
     */
    private function formatValueForAttribute(string $attributeCode, $value)
    {
        // Handle array values (e.g., for multiselect attributes)
        if (is_array($value)) {
            // If it's a JSON string in array, try to decode
            if (count($value) === 1 && is_string($value[0])) {
                $decoded = json_decode($value[0], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return implode(',', $decoded);
                }
            }
            return implode(',', $value);
        }

        // Handle JSON strings
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return implode(',', $decoded);
            }
        }

        return $value;
    }
}

