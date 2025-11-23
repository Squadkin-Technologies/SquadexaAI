<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

/**
 * Data modifier to inject AI-generated data into product form
 */
class AiDataModifier extends AbstractModifier
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param Json $jsonSerializer
     * @param Registry $registry
     * @param LocatorInterface $locator
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        Json $jsonSerializer,
        Registry $registry,
        LocatorInterface $locator,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonSerializer = $jsonSerializer;
        $this->registry = $registry;
        $this->locator = $locator;
        $this->logger = $logger;
    }

    /**
     * Custom data key to store AI product ID in product object
     */
    public const AI_PRODUCT_ID_KEY = 'ai_product_id';

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        // Only process for new products (no entity_id) and when ai_data parameter exists
        $productId = $this->request->getParam('id');
        $aiProductIdParam = $this->request->getParam('ai_data');

        if (!$productId && $aiProductIdParam) {
            try {
                $aiProductId = (int)$aiProductIdParam;
                
                $this->logger->info('AiDataModifier: Processing AI data for form', [
                    'ai_product_id' => $aiProductId
                ]);

                // Get product from locator (this is the standard way in Magento)
                $product = $this->locator->getProduct();
                
                // Store AI product ID in product data for observer access
                $product->setData(self::AI_PRODUCT_ID_KEY, $aiProductId);
                
                $productId = $product->getId();
                
                // For new products, Magento uses the product object's internal ID or 'new'
                // The Eav modifier expects data in format: [product_id => [static::DATA_SOURCE_DEFAULT => [attributes]]]
                $formDataKey = $productId ?: 'new';
                
                // Initialize data structure if needed
                if (!isset($data[$formDataKey])) {
                    $data[$formDataKey] = [];
                }
                if (!isset($data[$formDataKey][static::DATA_SOURCE_DEFAULT])) {
                    $data[$formDataKey][static::DATA_SOURCE_DEFAULT] = [];
                }

                // Store AI product ID in form data as well (for potential frontend use)
                $data[$formDataKey][static::DATA_SOURCE_DEFAULT][self::AI_PRODUCT_ID_KEY] = $aiProductId;

                $this->logger->info('AiDataModifier: AI product ID stored in product data', [
                    'ai_product_id' => $aiProductId,
                    'form_data_key' => $formDataKey
                ]);

            } catch (\Exception $e) {
                $this->logger->error('AiDataModifier: Error processing AI product ID', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        // Add hidden field to store AI product ID in form
        $productId = $this->request->getParam('id');
        $aiProductIdParam = $this->request->getParam('ai_data');

        if (!$productId && $aiProductIdParam) {
            // Add hidden field in general section to store AI product ID
            // This ensures it's available in POST data when form is submitted
            if (!isset($meta['general']['children'])) {
                $meta['general']['children'] = [];
            }
            
            $meta['general']['children'][self::AI_PRODUCT_ID_KEY] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => 'field',
                            'formElement' => 'input',
                            'dataType' => 'text',
                            'visible' => false,
                            'dataScope' => self::AI_PRODUCT_ID_KEY,
                            'value' => (int)$aiProductIdParam,
                            'default' => (int)$aiProductIdParam
                        ]
                    ]
                ]
            ];
        }

        return $meta;
    }
}
