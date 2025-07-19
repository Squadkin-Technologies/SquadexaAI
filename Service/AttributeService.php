<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Service;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;

class AttributeService
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Config $eavConfig
     * @param CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Config $eavConfig,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->eavConfig = $eavConfig;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * Get all product attributes for dropdown
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAttributesForDropdown()
    {
        $attributes = [];
        
        try {
            // Get all product attributes using collection
            $collection = $this->attributeCollectionFactory->create();
            $collection->addFieldToFilter('entity_type_id', 
                $this->eavConfig->getEntityType(Product::ENTITY)->getId()
            );
            
            // Add sorting to get user-defined attributes first, then system attributes
            $collection->addOrder('is_user_defined', 'DESC');
            $collection->addOrder('frontend_label', 'ASC');
            
            foreach ($collection as $attribute) {
                if ($this->isAttributeSelectable($attribute)) {
                    $attributes[] = [
                        'value' => $attribute->getAttributeCode(),
                        'label' => $attribute->getDefaultFrontendLabel() ?: $attribute->getAttributeCode(),
                        'type' => $attribute->getFrontendInput(),
                        'required' => $attribute->getIsRequired(),
                        'options' => $this->getAttributeOptions($attribute),
                        'is_user_defined' => $attribute->getIsUserDefined(),
                        'is_system' => !$attribute->getIsUserDefined()
                    ];
                }
            }
            
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error fetching attributes: %1', $e->getMessage()));
        }
        
        return $attributes;
    }

    /**
     * Check if attribute is selectable for custom attributes
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function isAttributeSelectable(AttributeInterface $attribute)
    {
        // Get attributes that are already handled in the main form
        $mainFormAttributes = $this->getMainFormAttributes();
        
        // Skip attributes that are already in the main form
        if (in_array($attribute->getAttributeCode(), $mainFormAttributes)) {
            return false;
        }
        
        // Skip attributes that shouldn't be displayed in forms
        if ($this->shouldSkipAttribute($attribute)) {
            return false;
        }
        
        // Include all other attributes that can be displayed
        return true;
    }

    /**
     * Get attributes that are already handled in the main form
     *
     * @return array
     */
    private function getMainFormAttributes()
    {
        return [
            'sku', 'name', 'description', 'short_description', 'price', 'special_price',
            'weight', 'status', 'visibility', 'tax_class_id', 'meta_title',
            'meta_description', 'meta_keyword', 'url_key', 'category_ids',
            'qty' // This is handled in our form but not a product attribute
        ];
    }

    /**
     * Check if attribute should be skipped
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function shouldSkipAttribute(AttributeInterface $attribute)
    {
        // Skip hidden attributes
        if ($attribute->getFrontendInput() === 'hidden') {
            return true;
        }
        
        // Skip media attributes (handled separately)
        if (in_array($attribute->getFrontendInput(), ['media_image', 'gallery'])) {
            return true;
        }
        
        // Skip system attributes that are not meant for user input
        $systemSkipAttributes = [
            'created_at', 'updated_at', 'has_options', 'required_options',
            'tier_price', 'recurring_profile', 'minimal_price', 'cost',
            'custom_design', 'custom_design_from', 'custom_design_to',
            'custom_layout_update', 'page_layout', 'options_container',
            'gift_message_available', 'price_type', 'sku_type', 'weight_type',
            'shipment_type', 'links_purchased_separately', 'samples_title',
            'links_title', 'links_exist', 'url_path', 'image_label',
            'small_image_label', 'thumbnail_label', 'created_in', 'updated_in',
            'image', 'small_image', 'thumbnail', 'media_gallery'
        ];
        
        if (in_array($attribute->getAttributeCode(), $systemSkipAttributes)) {
            return true;
        }
        
        // Skip attributes that are not visible and not user-defined
        if (!$attribute->getIsVisible() && !$attribute->getIsUserDefined()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get attribute options for select/multiselect attributes
     *
     * @param AttributeInterface $attribute
     * @return array
     */
    private function getAttributeOptions(AttributeInterface $attribute)
    {
        $options = [];
        
        if (in_array($attribute->getFrontendInput(), ['select', 'multiselect'])) {
            try {
                $attributeOptions = $attribute->getOptions();
                if ($attributeOptions) {
                    foreach ($attributeOptions as $option) {
                        if ($option->getValue() && $option->getValue() !== '') {
                            $options[] = [
                                'value' => $option->getValue(),
                                'label' => $option->getLabel()
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // If we can't get options, return empty array
            }
        }
        
        return $options;
    }

    /**
     * Get attribute by code
     *
     * @param string $attributeCode
     * @return AttributeInterface|null
     */
    public function getAttributeByCode($attributeCode)
    {
        try {
            return $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate if attribute exists in Magento
     *
     * @param string $attributeCode
     * @return bool
     */
    public function isValidMagentoAttribute($attributeCode)
    {
        try {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
            return $attribute && $attribute->getId();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get attribute details including options
     *
     * @param string $attributeCode
     * @return array|null
     */
    public function getAttributeDetails($attributeCode)
    {
        try {
            $attribute = $this->getAttributeByCode($attributeCode);
            if (!$attribute || !$attribute->getId()) {
                return null;
            }

            return [
                'code' => $attribute->getAttributeCode(),
                'label' => $attribute->getDefaultFrontendLabel() ?: $attribute->getAttributeCode(),
                'type' => $attribute->getFrontendInput(),
                'required' => $attribute->getIsRequired(),
                'options' => $this->getAttributeOptions($attribute),
                'is_system' => !$attribute->getIsUserDefined(),
                'is_visible' => $attribute->getIsVisible(),
                'is_searchable' => $attribute->getIsSearchable(),
                'is_filterable' => $attribute->getIsFilterable(),
                'is_comparable' => $attribute->getIsComparable(),
                'is_used_for_promo_rules' => $attribute->getIsUsedForPromoRules(),
                'position' => $attribute->getPosition(),
                'note' => $attribute->getNote()
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all available attribute types dynamically
     *
     * @return array
     */
    public function getAvailableAttributeTypes()
    {
        $types = [];
        
        try {
            $collection = $this->attributeCollectionFactory->create();
            $collection->addFieldToFilter('entity_type_id', 
                $this->eavConfig->getEntityType(Product::ENTITY)->getId()
            );
            $collection->addFieldToSelect('frontend_input');
            $collection->getSelect()->group('frontend_input');
            
            foreach ($collection as $attribute) {
                $frontendInput = $attribute->getFrontendInput();
                if ($frontendInput && !in_array($frontendInput, ['hidden', 'media_image', 'gallery'])) {
                    $types[] = $frontendInput;
                }
            }
            
        } catch (\Exception $e) {
            // Return common types as fallback
            $types = ['text', 'textarea', 'select', 'multiselect', 'boolean', 'date', 'price', 'weight'];
        }
        
        return array_unique($types);
    }

    /**
     * Get attributes by type
     *
     * @param string $type
     * @return array
     */
    public function getAttributesByType($type)
    {
        $attributes = [];
        
        try {
            $collection = $this->attributeCollectionFactory->create();
            $collection->addFieldToFilter('entity_type_id', 
                $this->eavConfig->getEntityType(Product::ENTITY)->getId()
            );
            $collection->addFieldToFilter('frontend_input', $type);
            
            foreach ($collection as $attribute) {
                if ($this->isAttributeSelectable($attribute)) {
                    $attributes[] = [
                        'value' => $attribute->getAttributeCode(),
                        'label' => $attribute->getDefaultFrontendLabel() ?: $attribute->getAttributeCode(),
                        'type' => $attribute->getFrontendInput(),
                        'required' => $attribute->getIsRequired(),
                        'options' => $this->getAttributeOptions($attribute)
                    ];
                }
            }
            
        } catch (\Exception $e) {
            // Return empty array on error
        }
        
        return $attributes;
    }
} 