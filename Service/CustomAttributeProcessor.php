<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface;

class CustomAttributeProcessor
{
    /**
     * @var AttributeService
     */
    private $attributeService;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @param AttributeService $attributeService
     * @param Json $jsonSerializer
     */
    public function __construct(
        AttributeService $attributeService,
        Json $jsonSerializer
    ) {
        $this->attributeService = $attributeService;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Process custom attributes from form data
     *
     * @param array $formData
     * @return array
     */
    public function processCustomAttributesFromForm(array $formData)
    {
        $customAttributes = [];
        
        if (!isset($formData['custom_attributes']) || !is_array($formData['custom_attributes'])) {
            return $customAttributes;
        }
        
        foreach ($formData['custom_attributes'] as $attributeData) {
            if (!empty($attributeData['attribute_code']) && isset($attributeData['value'])) {
                $customAttributes[$attributeData['attribute_code']] = $attributeData['value'];
            }
        }
        
        return $customAttributes;
    }

    /**
     * Apply custom attributes to Magento product
     *
     * @param ProductInterface $product
     * @param AiProductInterface $aiProduct
     * @return ProductInterface
     * @throws LocalizedException
     */
    public function applyCustomAttributesToProduct(ProductInterface $product, AiProductInterface $aiProduct)
    {
        $customAttributes = $aiProduct->getCustomAttributes();
        
        if (empty($customAttributes)) {
            return $product;
        }
        
        foreach ($customAttributes as $attributeCode => $value) {
            if ($this->attributeService->isValidMagentoAttribute($attributeCode)) {
                $this->setMagentoAttribute($product, $attributeCode, $value);
            }
        }
        
        return $product;
    }

    /**
     * Set Magento attribute value
     *
     * @param ProductInterface $product
     * @param string $attributeCode
     * @param mixed $value
     * @return void
     */
    private function setMagentoAttribute(ProductInterface $product, string $attributeCode, $value)
    {
        try {
            $attribute = $this->attributeService->getAttributeByCode($attributeCode);
            
            if (!$attribute) {
                return;
            }
            
            // Handle different attribute types
            switch ($attribute->getFrontendInput()) {
                case 'select':
                case 'multiselect':
                    $this->setSelectAttribute($product, $attributeCode, $value, $attribute);
                    break;
                case 'boolean':
                    $product->setData($attributeCode, (bool)$value);
                    break;
                case 'date':
                    if ($value) {
                        $product->setData($attributeCode, date('Y-m-d', strtotime($value)));
                    }
                    break;
                case 'price':
                    $product->setData($attributeCode, (float)$value);
                    break;
                case 'weight':
                    $product->setData($attributeCode, (float)$value);
                    break;
                default:
                    $product->setData($attributeCode, $value);
                    break;
            }
        } catch (\Exception $e) {
            // Log error but don't fail the entire process
            // You might want to add proper logging here
        }
    }

    /**
     * Set select/multiselect attribute value
     *
     * @param ProductInterface $product
     * @param string $attributeCode
     * @param mixed $value
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     * @return void
     */
    private function setSelectAttribute(ProductInterface $product, string $attributeCode, $value, $attribute)
    {
        if (empty($value)) {
            return;
        }
        
        try {
            $options = $attribute->getOptions();
            $optionMap = [];
            
            foreach ($options as $option) {
                if ($option->getValue()) {
                    $optionMap[strtolower($option->getLabel())] = $option->getValue();
                    $optionMap[$option->getValue()] = $option->getValue();
                }
            }
            
            if ($attribute->getFrontendInput() === 'multiselect') {
                $values = is_array($value) ? $value : explode(',', $value);
                $optionIds = [];
                
                foreach ($values as $val) {
                    $val = trim($val);
                    if (isset($optionMap[strtolower($val)])) {
                        $optionIds[] = $optionMap[strtolower($val)];
                    } elseif (isset($optionMap[$val])) {
                        $optionIds[] = $optionMap[$val];
                    }
                }
                
                if (!empty($optionIds)) {
                    $product->setData($attributeCode, implode(',', $optionIds));
                }
            } else {
                // Single select
                $val = trim($value);
                if (isset($optionMap[strtolower($val)])) {
                    $product->setData($attributeCode, $optionMap[strtolower($val)]);
                } elseif (isset($optionMap[$val])) {
                    $product->setData($attributeCode, $optionMap[$val]);
                }
            }
        } catch (\Exception $e) {
            // If we can't process select values, just set as text
            $product->setData($attributeCode, $value);
        }
    }

    /**
     * Get custom attributes that are not valid Magento attributes
     *
     * @param AiProductInterface $aiProduct
     * @return array
     */
    public function getCustomOnlyAttributes(AiProductInterface $aiProduct)
    {
        $customAttributes = $aiProduct->getCustomAttributes();
        $customOnlyAttributes = [];
        
        foreach ($customAttributes as $attributeCode => $value) {
            if (!$this->attributeService->isValidMagentoAttribute($attributeCode)) {
                $customOnlyAttributes[$attributeCode] = $value;
            }
        }
        
        return $customOnlyAttributes;
    }

    /**
     * Validate custom attributes data
     *
     * @param array $customAttributes
     * @return array Array of validation errors
     */
    public function validateCustomAttributes(array $customAttributes)
    {
        $errors = [];
        
        foreach ($customAttributes as $attributeCode => $value) {
            if (empty($attributeCode)) {
                $errors[] = __('Attribute code cannot be empty');
                continue;
            }
            
            // Check if attribute code is valid (alphanumeric and underscore only)
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $attributeCode)) {
                $errors[] = __('Attribute code "%1" is not valid. Use only letters, numbers, and underscores.', $attributeCode);
            }
            
            // Check if it's a valid Magento attribute
            if ($this->attributeService->isValidMagentoAttribute($attributeCode)) {
                $attribute = $this->attributeService->getAttributeByCode($attributeCode);
                if ($attribute && $attribute->getIsRequired() && empty($value)) {
                    $errors[] = __('Attribute "%1" is required', $attributeCode);
                }
            }
        }
        
        return $errors;
    }

    /**
     * Merge custom attributes with existing ones
     *
     * @param array $existingAttributes
     * @param array $newAttributes
     * @return array
     */
    public function mergeCustomAttributes(array $existingAttributes, array $newAttributes)
    {
        return array_merge($existingAttributes, $newAttributes);
    }

    /**
     * Format custom attributes for display
     *
     * @param array $customAttributes
     * @return array
     */
    public function formatCustomAttributesForDisplay(array $customAttributes)
    {
        $formatted = [];
        
        foreach ($customAttributes as $attributeCode => $value) {
            $attribute = $this->attributeService->getAttributeByCode($attributeCode);
            $label = $attribute ? $attribute->getDefaultFrontendLabel() : ucwords(str_replace('_', ' ', $attributeCode));
            
            $formatted[] = [
                'code' => $attributeCode,
                'label' => $label ?: $attributeCode,
                'value' => $value,
                'is_magento_attribute' => $this->attributeService->isValidMagentoAttribute($attributeCode)
            ];
        }
        
        return $formatted;
    }
} 