<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model\AiProduct;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Squadkin\SquadexaAI\Model\ResourceModel\AiProduct\CollectionFactory;
use Squadkin\SquadexaAI\Service\CustomAttributeProcessor;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var CustomAttributeProcessor
     */
    private $customAttributeProcessor;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param CustomAttributeProcessor $customAttributeProcessor
     * @param Json $jsonSerializer
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        CustomAttributeProcessor $customAttributeProcessor,
        Json $jsonSerializer,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->customAttributeProcessor = $customAttributeProcessor;
        $this->jsonSerializer = $jsonSerializer;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $data = $model->getData();
            
            // Process JSON fields - convert JSON strings to readable format for form display
            $jsonFields = ['key_features', 'how_to_use', 'ingredients', 'keywords'];
            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && !empty($data[$field])) {
                    try {
                        // Try to decode JSON, if successful convert array to readable format
                        $decoded = $this->jsonSerializer->unserialize($data[$field]);
                        if (is_array($decoded)) {
                            // For keywords, use comma-separated; for others, use newline-separated
                            if ($field === 'keywords') {
                                $data[$field] = implode(', ', $decoded);
                            } else {
                                $data[$field] = implode("\n", $decoded);
                            }
                        }
                    } catch (\Exception $e) {
                        // If JSON decode fails, keep the original value (might be plain text)
                        // This handles cases where data might not be JSON
                    }
                }
            }
            
            // Process custom attributes for form display
            $customAttributes = $model->getCustomAttributes();
            if (!empty($customAttributes)) {
                $data['custom_attributes'] = $this->formatCustomAttributesForForm($customAttributes);
            }
            
            $this->loadedData[$model->getId()] = $data;
        }
        
        $data = $this->dataPersistor->get('squadkin_squadexaai_aiproduct');
        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('squadkin_squadexaai_aiproduct');
        }
        
        return $this->loadedData;
    }

    /**
     * Get meta
     *
     * @return array
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        
        // Check if current product is created in Magento
        $aiproductId = $this->request->getParam('aiproduct_id');
        
        if ($aiproductId) {
            $item = $this->collection->getItemById($aiproductId);
            if ($item && $item->getIsCreatedInMagento()) {
                // Product is created in Magento - make all fields read-only
                $fieldsets = ['general', 'seo', 'product_details', 'pricing'];
                
                foreach ($fieldsets as $fieldset) {
                    if (isset($meta[$fieldset]['children'])) {
                        foreach ($meta[$fieldset]['children'] as $fieldName => &$fieldConfig) {
                            if (isset($fieldConfig['arguments']['data']['config'])) {
                                $fieldConfig['arguments']['data']['config']['disabled'] = true;
                                $fieldConfig['arguments']['data']['config']['readonly'] = true;
                            }
                        }
                    }
                }
            }
        }
        
        return $meta;
    }

    /**
     * Format custom attributes for form display
     *
     * @param array $customAttributes
     * @return array
     */
    private function formatCustomAttributesForForm(array $customAttributes)
    {
        $formattedAttributes = [];
        
        foreach ($customAttributes as $attributeCode => $value) {
            $formattedAttributes[] = [
                'attribute_code' => $attributeCode,
                'value' => $value
            ];
        }
        
        return $formattedAttributes;
    }
} 