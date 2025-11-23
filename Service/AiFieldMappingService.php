<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Service;

use Squadkin\SquadexaAI\Helper\FieldMapping as FieldMappingHelper;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * AI Field Mapping Service
 * Maps AI-generated product data to Magento product attributes using system configuration
 */
class AiFieldMappingService
{
    /**
     * @var FieldMappingHelper
     */
    private $fieldMappingHelper;

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
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param FieldMappingHelper $fieldMappingHelper
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        FieldMappingHelper $fieldMappingHelper,
        AiProductRepositoryInterface $aiProductRepository,
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        Json $jsonSerializer,
        LoggerInterface $logger,
        UrlInterface $urlBuilder
    ) {
        $this->fieldMappingHelper = $fieldMappingHelper;
        $this->aiProductRepository = $aiProductRepository;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Map AI product data to Magento product
     *
     * @param int $aiProductId
     * @param string $productType
     * @param int|null $attributeSetId
     * @param int|null $mappingId Unused - kept for backward compatibility
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
            // Validate that field mappings are configured
            $this->validateFieldMappings('creating products');

            // Get AI Product
            $aiProduct = $this->aiProductRepository->get($aiProductId);

            // Get AI product data
            $aiData = $this->extractAiProductData($aiProduct);

            // Map fields using system configuration
            $magentoData = $this->fieldMappingHelper->mapAiDataToMagento($aiData);

            // Add product type and attribute set
            $magentoData['type_id'] = $productType;
            if ($attributeSetId) {
                $magentoData['attribute_set_id'] = $attributeSetId;
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

            $this->logger->info('AI Field Mapping: Successfully mapped AI product to Magento', [
                'ai_product_id' => $aiProductId,
                'product_type' => $productType
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
     * @param int|null $mappingId Unused - kept for backward compatibility
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
            // Validate that field mappings are configured
            $this->validateFieldMappings('updating products');

            // Get existing product
            $product = $this->productRepository->getById($productId);

            // Get AI Product
            $aiProduct = $this->aiProductRepository->get($aiProductId);

            // Get AI product data (use provided data or extract from AI product)
            $aiProductData = !empty($aiData) ? $aiData : $this->extractAiProductData($aiProduct);

            // Map fields using system configuration
            $mappedData = $this->fieldMappingHelper->mapAiDataToMagento($aiProductData);

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
     * Validate that field mappings are configured
     *
     * @param string $action Description of the action (e.g., 'creating products', 'updating products')
     * @return void
     * @throws LocalizedException
     */
    private function validateFieldMappings(string $action): void
    {
        $fieldMappings = $this->fieldMappingHelper->getFieldMappings();
        if (empty($fieldMappings)) {
            $configUrl = $this->urlBuilder->getUrl(
                'adminhtml/system_config/edit/section/squadexaiproductcreator',
                ['_fragment' => 'squadexaiproductcreator_field_mapping-link']
            );
            throw new LocalizedException(__(
                'Field mapping configuration is required before %1 from AI data. ' .
                'Please configure field mappings in ' .
                '<a href="%2" target="_blank">System Configuration → Field Mapping</a>. ' .
                'Field mappings tell the system which Magento product attributes ' .
                'to use for each AI-generated field.',
                $action,
                $configUrl
            ));
        }
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
}
