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
        $value = $this->getData(self::GENERATEDCSV_ID);
        return $value !== null ? (int)$value : null;
    }

    /**
     * @inheritDoc
     */
    public function setGeneratedcsvId($generatedcsvId)
    {
        // Explicitly set to null if null is passed, otherwise set the value
        if ($generatedcsvId === null) {
            return $this->setData(self::GENERATEDCSV_ID, null);
        }
        return $this->setData(self::GENERATEDCSV_ID, $generatedcsvId);
    }

    /**
     * @inheritDoc
     */
    public function getProductName()
    {
        return $this->getData(self::PRODUCT_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setProductName($productName)
    {
        return $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalInformation()
    {
        return $this->getData(self::ADDITIONAL_INFORMATION);
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalInformation($additionalInformation)
    {
        return $this->setData(self::ADDITIONAL_INFORMATION, $additionalInformation);
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
        return $this->getData(self::ADDITIONAL_INFORMATION);
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalData($additionalData)
    {
        return $this->setData(self::ADDITIONAL_INFORMATION, $additionalData);
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

    /**
     * @inheritDoc
     */
    public function getKeyFeatures()
    {
        return $this->getData(self::KEY_FEATURES);
    }

    /**
     * @inheritDoc
     */
    public function setKeyFeatures($keyFeatures)
    {
        return $this->setData(self::KEY_FEATURES, $keyFeatures);
    }

    /**
     * @inheritDoc
     */
    public function getHowToUse()
    {
        return $this->getData(self::HOW_TO_USE);
    }

    /**
     * @inheritDoc
     */
    public function setHowToUse($howToUse)
    {
        return $this->setData(self::HOW_TO_USE, $howToUse);
    }

    /**
     * @inheritDoc
     */
    public function getIngredients()
    {
        return $this->getData(self::INGREDIENTS);
    }

    /**
     * @inheritDoc
     */
    public function setIngredients($ingredients)
    {
        return $this->setData(self::INGREDIENTS, $ingredients);
    }

    /**
     * @inheritDoc
     */
    public function getUpc()
    {
        return $this->getData(self::UPC);
    }

    /**
     * @inheritDoc
     */
    public function setUpc($upc)
    {
        return $this->setData(self::UPC, $upc);
    }

    /**
     * @inheritDoc
     */
    public function getKeywords()
    {
        return $this->getData(self::KEYWORDS);
    }

    /**
     * @inheritDoc
     */
    public function setKeywords($keywords)
    {
        return $this->setData(self::KEYWORDS, $keywords);
    }

    /**
     * @inheritDoc
     */
    public function getPricingUsdMin()
    {
        return $this->getData(self::PRICING_USD_MIN);
    }

    /**
     * @inheritDoc
     */
    public function setPricingUsdMin($pricingUsdMin)
    {
        return $this->setData(self::PRICING_USD_MIN, $pricingUsdMin);
    }

    /**
     * @inheritDoc
     */
    public function getPricingUsdMax()
    {
        return $this->getData(self::PRICING_USD_MAX);
    }

    /**
     * @inheritDoc
     */
    public function setPricingUsdMax($pricingUsdMax)
    {
        return $this->setData(self::PRICING_USD_MAX, $pricingUsdMax);
    }

    /**
     * @inheritDoc
     */
    public function getPricingCadMin()
    {
        return $this->getData(self::PRICING_CAD_MIN);
    }

    /**
     * @inheritDoc
     */
    public function setPricingCadMin($pricingCadMin)
    {
        return $this->setData(self::PRICING_CAD_MIN, $pricingCadMin);
    }

    /**
     * @inheritDoc
     */
    public function getPricingCadMax()
    {
        return $this->getData(self::PRICING_CAD_MAX);
    }

    /**
     * @inheritDoc
     */
    public function setPricingCadMax($pricingCadMax)
    {
        return $this->setData(self::PRICING_CAD_MAX, $pricingCadMax);
    }

    /**
     * Get AI response
     *
     * @return string|null
     */
    public function getAiResponse()
    {
        return $this->getData(self::AI_RESPONSE);
    }

    /**
     * Set AI response
     *
     * @param string|null $aiResponse
     * @return $this
     */
    public function setAiResponse($aiResponse)
    {
        return $this->setData(self::AI_RESPONSE, $aiResponse);
    }

    /**
     * Get generation type
     *
     * @return string|null
     */
    public function getGenerationType()
    {
        return $this->getData(self::GENERATION_TYPE);
    }

    /**
     * Set generation type
     *
     * @param string $generationType
     * @return $this
     */
    public function setGenerationType($generationType)
    {
        return $this->setData(self::GENERATION_TYPE, $generationType);
    }

    /**
     * Get primary keywords
     *
     * @return string|null
     */
    public function getPrimaryKeywords()
    {
        return $this->getData(self::PRIMARY_KEYWORDS);
    }

    /**
     * Set primary keywords
     *
     * @param string|null $primaryKeywords
     * @return $this
     */
    public function setPrimaryKeywords($primaryKeywords)
    {
        return $this->setData(self::PRIMARY_KEYWORDS, $primaryKeywords);
    }

    /**
     * Get secondary keywords
     *
     * @return string|null
     */
    public function getSecondaryKeywords()
    {
        return $this->getData(self::SECONDARY_KEYWORDS);
    }

    /**
     * Set secondary keywords
     *
     * @param string|null $secondaryKeywords
     * @return $this
     */
    public function setSecondaryKeywords($secondaryKeywords)
    {
        return $this->setData(self::SECONDARY_KEYWORDS, $secondaryKeywords);
    }

    /**
     * Get regeneration count
     *
     * @return int
     */
    public function getRegenerationCount()
    {
        return (int)($this->getData('regeneration_count') ?? 0);
    }

    /**
     * Set regeneration count
     *
     * @param int $count
     * @return $this
     */
    public function setRegenerationCount($count)
    {
        return $this->setData('regeneration_count', (int)$count);
    }
} 