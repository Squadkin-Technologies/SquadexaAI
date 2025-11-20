<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Plugin\Catalog\Controller\Adminhtml\Product;

use Magento\Catalog\Controller\Adminhtml\Product\Builder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Plugin to pre-fill product form with AI-generated data
 */
class BuilderPlugin
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Custom data key to store AI product ID in product object
     */
    const AI_PRODUCT_ID_KEY = 'ai_product_id';

    /**
     * Pre-fill product with AI data after build
     *
     * @param Builder $subject
     * @param \Magento\Catalog\Api\Data\ProductInterface $result
     * @param RequestInterface $request
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function afterBuild(
        Builder $subject,
        \Magento\Catalog\Api\Data\ProductInterface $result,
        RequestInterface $request
    ): \Magento\Catalog\Api\Data\ProductInterface {
        // Store AI product ID if present (for observer access)
        $aiProductIdParam = $request->getParam('ai_data');
        if ($aiProductIdParam && !$result->getId()) {
            $aiProductId = (int)$aiProductIdParam;
            if ($aiProductId > 0) {
                $result->setData(self::AI_PRODUCT_ID_KEY, $aiProductId);
                $this->logger->info('BuilderPlugin: Stored AI product ID in product data', [
                    'ai_product_id' => $aiProductId
                ]);
            }
        }
        
        // Only apply for new products (no ID) and when ai_data parameter exists
        if (!$result->getId() && $aiProductIdParam) {
            try {
                $aiDataEncoded = $request->getParam('ai_data');
                $this->logger->info('BuilderPlugin: Processing AI data', [
                    'ai_data_param_exists' => !empty($aiDataEncoded),
                    'ai_data_length' => strlen($aiDataEncoded ?? '')
                ]);

                // Decode base64 and then JSON
                $decodedBase64 = base64_decode($aiDataEncoded, true);
                if ($decodedBase64 === false) {
                    $this->logger->error('BuilderPlugin: Failed to decode base64 data');
                    return $result;
                }

                $aiData = $this->jsonSerializer->unserialize($decodedBase64);
                
                if (!is_array($aiData)) {
                    $this->logger->error('BuilderPlugin: Decoded data is not an array', [
                        'type' => gettype($aiData)
                    ]);
                    return $result;
                }

                $this->logger->info('BuilderPlugin: Decoded AI data', [
                    'attributes_count' => count($aiData),
                    'attributes' => array_keys($aiData),
                    'sample_data' => array_slice($aiData, 0, 5, true)
                ]);

                if (!empty($aiData)) {
                    // Apply mapped AI data to product using addData for bulk assignment
                    $dataToSet = [];
                    
                    foreach ($aiData as $attributeCode => $value) {
                        if ($value !== null && $value !== '') {
                            // Handle array values (for multiselect, etc.)
                            if (is_array($value)) {
                                $dataToSet[$attributeCode] = $value;
                            } elseif (is_string($value) && !empty(trim($value))) {
                                $dataToSet[$attributeCode] = trim($value);
                            } elseif (is_numeric($value)) {
                                $dataToSet[$attributeCode] = $value;
                            } else {
                                $dataToSet[$attributeCode] = $value;
                            }
                        }
                    }

                    if (!empty($dataToSet)) {
                        // Ensure product has attribute set before setting data
                        // This is important for EAV attributes to be set correctly
                        if (!$result->getAttributeSetId() && isset($dataToSet['attribute_set_id'])) {
                            $result->setAttributeSetId($dataToSet['attribute_set_id']);
                        }
                        
                        // Use addData for bulk assignment - this is more efficient
                        $result->addData($dataToSet);
                        
                        // Also ensure the data is set in the product's data array
                        // This ensures the data is available when the form loads
                        foreach ($dataToSet as $key => $val) {
                            $result->setData($key, $val);
                        }
                        
                        // Mark product as having data changes so form recognizes the values
                        $result->setHasDataChanges(true);

                        $this->logger->info('BuilderPlugin: AI Product data applied to Magento product form', [
                            'attributes_set_count' => count($dataToSet),
                            'attributes_set' => array_keys($dataToSet),
                            'product_id' => $result->getId(),
                            'product_sku' => $result->getSku(),
                            'product_name' => $result->getName(),
                            'attribute_set_id' => $result->getAttributeSetId(),
                            'type_id' => $result->getTypeId()
                        ]);
                    } else {
                        $this->logger->warning('BuilderPlugin: No valid data to set after filtering');
                    }
                } else {
                    $this->logger->warning('BuilderPlugin: AI data array is empty');
                }
            } catch (\Exception $e) {
                $this->logger->error('BuilderPlugin: Error applying AI data to product', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            $this->logger->debug('BuilderPlugin: Skipping - product has ID or no ai_data param', [
                'product_id' => $result->getId(),
                'has_ai_data' => $request->getParam('ai_data') !== null
            ]);
        }

        return $result;
    }
}

