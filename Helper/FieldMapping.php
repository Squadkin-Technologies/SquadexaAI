<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Helper for Field Mapping Configuration
 */
class FieldMapping extends AbstractHelper
{
    const XML_PATH_FIELD_MAPPING = 'squadexaiproductcreator/field_mapping/default_mapping_rules';

    /**
     * @var DecoderInterface
     */
    private $jsonDecoder;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @param Context $context
     * @param DecoderInterface $jsonDecoder
     * @param Json $jsonSerializer
     */
    public function __construct(
        Context $context,
        DecoderInterface $jsonDecoder,
        Json $jsonSerializer
    ) {
        parent::__construct($context);
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Get field mapping configuration
     *
     * @param int|null $storeId
     * @return array
     */
    public function getFieldMappings(?int $storeId = null): array
    {
        $mappingJson = $this->scopeConfig->getValue(
            self::XML_PATH_FIELD_MAPPING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($mappingJson)) {
            return [];
        }

        try {
            // Try JSON decode first
            $mappings = $this->jsonDecoder->decode($mappingJson);
            if (is_array($mappings)) {
                return $mappings;
            }
        } catch (\Exception $e) {
            // If JSON decode fails, try unserialize
            try {
                $mappings = $this->jsonSerializer->unserialize($mappingJson);
                if (is_array($mappings)) {
                    return $mappings;
                }
            } catch (\Exception $e2) {
                $this->_logger->error('Error decoding field mapping configuration: ' . $e2->getMessage());
            }
        }

        return [];
    }

    /**
     * Get mapped Magento attribute code for an AI field
     *
     * @param string $aiFieldCode
     * @param int|null $storeId
     * @return string|null
     */
    public function getMappedAttribute(string $aiFieldCode, ?int $storeId = null): ?string
    {
        $mappings = $this->getFieldMappings($storeId);
        return $mappings[$aiFieldCode] ?? null;
    }

    /**
     * Check if a mapping exists for an AI field
     *
     * @param string $aiFieldCode
     * @param int|null $storeId
     * @return bool
     */
    public function hasMapping(string $aiFieldCode, ?int $storeId = null): bool
    {
        $mappings = $this->getFieldMappings($storeId);
        return isset($mappings[$aiFieldCode]) && !empty($mappings[$aiFieldCode]);
    }

    /**
     * Get all AI fields that have mappings
     *
     * @param int|null $storeId
     * @return array Array of AI field codes that have mappings
     */
    public function getMappedFields(?int $storeId = null): array
    {
        $mappings = $this->getFieldMappings($storeId);
        return array_keys(array_filter($mappings));
    }

    /**
     * Map AI product data to Magento product data using configured mappings
     *
     * @param array $aiProductData AI product data array
     * @param int|null $storeId
     * @return array Mapped data ready for Magento product
     */
    public function mapAiDataToMagento(array $aiProductData, ?int $storeId = null): array
    {
        $mappings = $this->getFieldMappings($storeId);
        $magentoData = [];

        foreach ($mappings as $aiField => $magentoAttribute) {
            if (isset($aiProductData[$aiField]) && !empty($aiProductData[$aiField])) {
                $magentoData[$magentoAttribute] = $aiProductData[$aiField];
            }
        }

        return $magentoData;
    }
}

