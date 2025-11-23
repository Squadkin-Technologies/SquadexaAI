<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Squadkin\SquadexaAI\Service\AttributeService;
use Psr\Log\LoggerInterface;

class AttributeOptions implements OptionSourceInterface
{
    /**
     * @var AttributeService
     */
    private $attributeService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $options = null;

    /**
     * @param AttributeService $attributeService
     * @param LoggerInterface $logger
     */
    public function __construct(
        AttributeService $attributeService,
        LoggerInterface $logger
    ) {
        $this->attributeService = $attributeService;
        $this->logger = $logger;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];
            
            try {
                $attributes = $this->attributeService->getAttributesForDropdown();
                
                // Add empty option at the beginning
                $this->options[] = [
                    'value' => '',
                    'label' => __('-- Please Select Attribute --')
                ];
                
                foreach ($attributes as $attribute) {
                    $label = $attribute['label'];
                    
                    // Add attribute type indicator
                    if (isset($attribute['type'])) {
                        $typeLabel = $this->getTypeLabel($attribute['type']);
                        if ($typeLabel) {
                            $label .= ' (' . $typeLabel . ')';
                        }
                    }
                    
                    // Add required indicator
                    if (isset($attribute['required']) && $attribute['required']) {
                        $label .= ' *';
                    }
                    
                    $this->options[] = [
                        'value' => $attribute['value'],
                        'label' => $label
                    ];
                }
                
            } catch (\Exception $e) {
                $this->logger->error('Error fetching attribute options: ' . $e->getMessage());
                // Return at least the empty option
                $this->options = [
                    [
                        'value' => '',
                        'label' => __('-- Error Loading Attributes --')
                    ]
                ];
            }
        }
        
        return $this->options;
    }

    /**
     * Get human-readable type label
     *
     * @param string $type
     * @return string
     */
    private function getTypeLabel($type)
    {
        $typeLabels = [
            'text' => __('Text'),
            'textarea' => __('Textarea'),
            'select' => __('Dropdown'),
            'multiselect' => __('Multi-Select'),
            'boolean' => __('Yes/No'),
            'date' => __('Date'),
            'price' => __('Price'),
            'weight' => __('Weight'),
            'media_image' => __('Image'),
            'gallery' => __('Gallery'),
            'file' => __('File'),
            'hidden' => __('Hidden'),
            'pagebuilder' => __('Page Builder')
        ];
        
        return isset($typeLabels[$type]) ? $typeLabels[$type] : ucfirst($type);
    }

    /**
     * Get options as hash array
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];
        foreach ($this->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }
}
