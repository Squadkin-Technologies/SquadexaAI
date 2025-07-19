<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Model\AiProduct;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Squadkin\AIAutoProductBuilder\Model\ResourceModel\AiProduct\CollectionFactory;
use Squadkin\AIAutoProductBuilder\Service\CustomAttributeProcessor;

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
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param CustomAttributeProcessor $customAttributeProcessor
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
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->customAttributeProcessor = $customAttributeProcessor;
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
            
            // Process custom attributes for form display
            $customAttributes = $model->getCustomAttributes();
            if (!empty($customAttributes)) {
                $data['custom_attributes'] = $this->formatCustomAttributesForForm($customAttributes);
            }
            
            $this->loadedData[$model->getId()] = $data;
        }
        
        $data = $this->dataPersistor->get('squadkin_aiautoproductbuilder_aiproduct');
        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('squadkin_aiautoproductbuilder_aiproduct');
        }
        
        return $this->loadedData;
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