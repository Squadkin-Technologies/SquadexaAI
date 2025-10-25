<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api\Data;

interface AiProductInterface
{
    const AIPRODUCT_ID = 'aiproduct_id';
    const GENERATEDCSV_ID = 'generatedcsv_id';
    const SKU = 'sku';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const SHORT_DESCRIPTION = 'short_description';
    const PRICE = 'price';
    const SPECIAL_PRICE = 'special_price';
    const WEIGHT = 'weight';
    const QTY = 'qty';
    const CATEGORY = 'category';
    const STATUS = 'status';
    const VISIBILITY = 'visibility';
    const TYPE = 'type';
    const ATTRIBUTE_SET = 'attribute_set';
    const TAX_CLASS = 'tax_class';
    const META_TITLE = 'meta_title';
    const META_DESCRIPTION = 'meta_description';
    const META_KEYWORDS = 'meta_keywords';
    const URL_KEY = 'url_key';
    const MAGENTO_PRODUCT_ID = 'magento_product_id';
    const IS_CREATED_IN_MAGENTO = 'is_created_in_magento';
    const ADDITIONAL_DATA = 'additional_data';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get aiproduct_id
     * @return string|null
     */
    public function getAiproductId();

    /**
     * Set aiproduct_id
     * @param string $aiproductId
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setAiproductId($aiproductId);

    /**
     * Get generatedcsv_id
     * @return int|null
     */
    public function getGeneratedcsvId();

    /**
     * Set generatedcsv_id
     * @param int $generatedcsvId
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setGeneratedcsvId($generatedcsvId);

    /**
     * Get sku
     * @return string|null
     */
    public function getSku();

    /**
     * Set sku
     * @param string $sku
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setSku($sku);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setName($name);

    /**
     * Get description
     * @return string|null
     */
    public function getDescription();

    /**
     * Set description
     * @param string $description
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setDescription($description);

    /**
     * Get short_description
     * @return string|null
     */
    public function getShortDescription();

    /**
     * Set short_description
     * @param string $shortDescription
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setShortDescription($shortDescription);

    /**
     * Get price
     * @return float|null
     */
    public function getPrice();

    /**
     * Set price
     * @param float $price
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setPrice($price);

    /**
     * Get special_price
     * @return float|null
     */
    public function getSpecialPrice();

    /**
     * Set special_price
     * @param float $specialPrice
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setSpecialPrice($specialPrice);

    /**
     * Get weight
     * @return float|null
     */
    public function getWeight();

    /**
     * Set weight
     * @param float $weight
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setWeight($weight);

    /**
     * Get qty
     * @return int|null
     */
    public function getQty();

    /**
     * Set qty
     * @param int $qty
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setQty($qty);

    /**
     * Get category
     * @return string|null
     */
    public function getCategory();

    /**
     * Set category
     * @param string $category
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setCategory($category);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setStatus($status);

    /**
     * Get visibility
     * @return string|null
     */
    public function getVisibility();

    /**
     * Set visibility
     * @param string $visibility
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setVisibility($visibility);

    /**
     * Get type
     * @return string|null
     */
    public function getType();

    /**
     * Set type
     * @param string $type
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setType($type);

    /**
     * Get attribute_set
     * @return string|null
     */
    public function getAttributeSet();

    /**
     * Set attribute_set
     * @param string $attributeSet
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setAttributeSet($attributeSet);

    /**
     * Get tax_class
     * @return string|null
     */
    public function getTaxClass();

    /**
     * Set tax_class
     * @param string $taxClass
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setTaxClass($taxClass);

    /**
     * Get meta_title
     * @return string|null
     */
    public function getMetaTitle();

    /**
     * Set meta_title
     * @param string $metaTitle
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setMetaTitle($metaTitle);

    /**
     * Get meta_description
     * @return string|null
     */
    public function getMetaDescription();

    /**
     * Set meta_description
     * @param string $metaDescription
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setMetaDescription($metaDescription);

    /**
     * Get meta_keywords
     * @return string|null
     */
    public function getMetaKeywords();

    /**
     * Set meta_keywords
     * @param string $metaKeywords
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setMetaKeywords($metaKeywords);

    /**
     * Get url_key
     * @return string|null
     */
    public function getUrlKey();

    /**
     * Set url_key
     * @param string $urlKey
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setUrlKey($urlKey);

    /**
     * Get magento_product_id
     * @return int|null
     */
    public function getMagentoProductId();

    /**
     * Set magento_product_id
     * @param int $magentoProductId
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setMagentoProductId($magentoProductId);

    /**
     * Get is_created_in_magento
     * @return bool|null
     */
    public function getIsCreatedInMagento();

    /**
     * Set is_created_in_magento
     * @param bool $isCreatedInMagento
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setIsCreatedInMagento($isCreatedInMagento);

    /**
     * Get additional_data
     * @return string|null
     */
    public function getAdditionalData();

    /**
     * Set additional_data
     * @param string $additionalData
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setAdditionalData($additionalData);

    /**
     * Get custom attributes as array
     * @return array
     */
    public function getCustomAttributes();

    /**
     * Set custom attributes
     * @param array $customAttributes
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setCustomAttributes($customAttributes);

    /**
     * Get custom attribute value by code
     * @param string $attributeCode
     * @return mixed|null
     */
    public function getCustomAttribute($attributeCode);

    /**
     * Set custom attribute value
     * @param string $attributeCode
     * @param mixed $value
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setCustomAttribute($attributeCode, $value);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface
     */
    public function setUpdatedAt($updatedAt);
} 