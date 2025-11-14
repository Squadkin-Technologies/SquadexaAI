<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model;

use Magento\Framework\Model\AbstractModel;
use Squadkin\SquadexaAI\Api\Data\FieldMappingInterface;
use Squadkin\SquadexaAI\Model\ResourceModel\FieldMapping as FieldMappingResource;

/**
 * Field Mapping Model
 */
class FieldMapping extends AbstractModel implements FieldMappingInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(FieldMappingResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getMappingId()
    {
        return $this->getData(self::MAPPING_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMappingId($mappingId)
    {
        return $this->setData(self::MAPPING_ID, $mappingId);
    }

    /**
     * @inheritDoc
     */
    public function getProfileName()
    {
        return $this->getData(self::PROFILE_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setProfileName($profileName)
    {
        return $this->setData(self::PROFILE_NAME, $profileName);
    }

    /**
     * @inheritDoc
     */
    public function getIsDefault()
    {
        return (bool)$this->getData(self::IS_DEFAULT);
    }

    /**
     * @inheritDoc
     */
    public function setIsDefault($isDefault)
    {
        return $this->setData(self::IS_DEFAULT, $isDefault);
    }

    /**
     * @inheritDoc
     */
    public function getProductType()
    {
        return $this->getData(self::PRODUCT_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setProductType($productType)
    {
        return $this->setData(self::PRODUCT_TYPE, $productType);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeSetId()
    {
        return $this->getData(self::ATTRIBUTE_SET_ID);
    }

    /**
     * @inheritDoc
     */
    public function setAttributeSetId($attributeSetId)
    {
        return $this->setData(self::ATTRIBUTE_SET_ID, $attributeSetId);
    }

    /**
     * @inheritDoc
     */
    public function getMappingRules()
    {
        return $this->getData(self::MAPPING_RULES);
    }

    /**
     * @inheritDoc
     */
    public function setMappingRules($mappingRules)
    {
        return $this->setData(self::MAPPING_RULES, $mappingRules);
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}

