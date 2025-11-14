<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api\Data;

/**
 * Field Mapping Interface
 */
interface FieldMappingInterface
{
    const MAPPING_ID = 'mapping_id';
    const PROFILE_NAME = 'profile_name';
    const IS_DEFAULT = 'is_default';
    const PRODUCT_TYPE = 'product_type';
    const ATTRIBUTE_SET_ID = 'attribute_set_id';
    const MAPPING_RULES = 'mapping_rules';
    const DESCRIPTION = 'description';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get Mapping ID
     *
     * @return int|null
     */
    public function getMappingId();

    /**
     * Set Mapping ID
     *
     * @param int $mappingId
     * @return $this
     */
    public function setMappingId($mappingId);

    /**
     * Get Profile Name
     *
     * @return string
     */
    public function getProfileName();

    /**
     * Set Profile Name
     *
     * @param string $profileName
     * @return $this
     */
    public function setProfileName($profileName);

    /**
     * Get Is Default
     *
     * @return bool
     */
    public function getIsDefault();

    /**
     * Set Is Default
     *
     * @param bool $isDefault
     * @return $this
     */
    public function setIsDefault($isDefault);

    /**
     * Get Product Type
     *
     * @return string|null
     */
    public function getProductType();

    /**
     * Set Product Type
     *
     * @param string|null $productType
     * @return $this
     */
    public function setProductType($productType);

    /**
     * Get Attribute Set ID
     *
     * @return int|null
     */
    public function getAttributeSetId();

    /**
     * Set Attribute Set ID
     *
     * @param int|null $attributeSetId
     * @return $this
     */
    public function setAttributeSetId($attributeSetId);

    /**
     * Get Mapping Rules (JSON string)
     *
     * @return string
     */
    public function getMappingRules();

    /**
     * Set Mapping Rules (JSON string)
     *
     * @param string $mappingRules
     * @return $this
     */
    public function setMappingRules($mappingRules);

    /**
     * Get Description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set Description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set Created At
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set Updated At
     *
     * @param string|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}

