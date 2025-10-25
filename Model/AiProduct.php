<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;
use Squadkin\SquadexaAI\Api\Data\AiProductInterface;

class AiProduct extends AbstractModel implements AiProductInterface
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Json $jsonSerializer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Json $jsonSerializer,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Squadkin\SquadexaAI\Model\ResourceModel\AiProduct::class);
    }

    /**
     * @inheritDoc
     */
    public function getAiproductId()
    {
        return $this->getData(self::AIPRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setAiproductId($aiproductId)
    {
        return $this->setData(self::AIPRODUCT_ID, $aiproductId);
    }

    /**
     * @inheritDoc
     */
    public function getGeneratedcsvId()
    {
        return (int)$this->getData(self::GENERATEDCSV_ID);
    }

    /**
     * @inheritDoc
     */
    public function setGeneratedcsvId($generatedcsvId)
    {
        return $this->setData(self::GENERATEDCSV_ID, $generatedcsvId);
    }

    /**
     * @inheritDoc
     */
    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    /**
     * @inheritDoc
     */
    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
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
    public function getShortDescription()
    {
        return $this->getData(self::SHORT_DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setShortDescription($shortDescription)
    {
        return $this->setData(self::SHORT_DESCRIPTION, $shortDescription);
    }

    /**
     * @inheritDoc
     */
    public function getPrice()
    {
        return $this->getData(self::PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritDoc
     */
    public function getSpecialPrice()
    {
        return $this->getData(self::SPECIAL_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setSpecialPrice($specialPrice)
    {
        return $this->setData(self::SPECIAL_PRICE, $specialPrice);
    }

    /**
     * @inheritDoc
     */
    public function getWeight()
    {
        return $this->getData(self::WEIGHT);
    }

    /**
     * @inheritDoc
     */
    public function setWeight($weight)
    {
        return $this->setData(self::WEIGHT, $weight);
    }

    /**
     * @inheritDoc
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * @inheritDoc
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * @inheritDoc
     */
    public function getCategory()
    {
        return $this->getData(self::CATEGORY);
    }

    /**
     * @inheritDoc
     */
    public function setCategory($category)
    {
        return $this->setData(self::CATEGORY, $category);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getVisibility()
    {
        return $this->getData(self::VISIBILITY);
    }

    /**
     * @inheritDoc
     */
    public function setVisibility($visibility)
    {
        return $this->setData(self::VISIBILITY, $visibility);
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeSet()
    {
        return $this->getData(self::ATTRIBUTE_SET);
    }

    /**
     * @inheritDoc
     */
    public function setAttributeSet($attributeSet)
    {
        return $this->setData(self::ATTRIBUTE_SET, $attributeSet);
    }

    /**
     * @inheritDoc
     */
    public function getTaxClass()
    {
        return $this->getData(self::TAX_CLASS);
    }

    /**
     * @inheritDoc
     */
    public function setTaxClass($taxClass)
    {
        return $this->setData(self::TAX_CLASS, $taxClass);
    }

    /**
     * @inheritDoc
     */
    public function getMetaTitle()
    {
        return $this->getData(self::META_TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setMetaTitle($metaTitle)
    {
        return $this->setData(self::META_TITLE, $metaTitle);
    }

    /**
     * @inheritDoc
     */
    public function getMetaDescription()
    {
        return $this->getData(self::META_DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setMetaDescription($metaDescription)
    {
        return $this->setData(self::META_DESCRIPTION, $metaDescription);
    }

    /**
     * @inheritDoc
     */
    public function getMetaKeywords()
    {
        return $this->getData(self::META_KEYWORDS);
    }

    /**
     * @inheritDoc
     */
    public function setMetaKeywords($metaKeywords)
    {
        return $this->setData(self::META_KEYWORDS, $metaKeywords);
    }

    /**
     * @inheritDoc
     */
    public function getUrlKey()
    {
        return $this->getData(self::URL_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setUrlKey($urlKey)
    {
        return $this->setData(self::URL_KEY, $urlKey);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoProductId()
    {
        return $this->getData(self::MAGENTO_PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoProductId($magentoProductId)
    {
        return $this->setData(self::MAGENTO_PRODUCT_ID, $magentoProductId);
    }

    /**
     * @inheritDoc
     */
    public function getIsCreatedInMagento()
    {
        return $this->getData(self::IS_CREATED_IN_MAGENTO);
    }

    /**
     * @inheritDoc
     */
    public function setIsCreatedInMagento($isCreatedInMagento)
    {
        return $this->setData(self::IS_CREATED_IN_MAGENTO, $isCreatedInMagento);
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalData()
    {
        return $this->getData(self::ADDITIONAL_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalData($additionalData)
    {
        return $this->setData(self::ADDITIONAL_DATA, $additionalData);
    }

    /**
     * @inheritDoc
     */
    public function getCustomAttributes()
    {
        $additionalData = $this->getAdditionalData();
        if (!$additionalData) {
            return [];
        }

        try {
            return $this->jsonSerializer->unserialize($additionalData);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function setCustomAttributes($customAttributes)
    {
        if (empty($customAttributes)) {
            $this->setAdditionalData(null);
        } else {
            $this->setAdditionalData($this->jsonSerializer->serialize($customAttributes));
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCustomAttribute($attributeCode)
    {
        $customAttributes = $this->getCustomAttributes();
        return $customAttributes[$attributeCode] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomAttribute($attributeCode, $value)
    {
        $customAttributes = $this->getCustomAttributes();
        $customAttributes[$attributeCode] = $value;
        $this->setCustomAttributes($customAttributes);
        return $this;
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