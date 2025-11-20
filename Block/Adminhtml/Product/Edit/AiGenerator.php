<?php
/**
 * Squadexa AI Product generator block
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\Product\Edit;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

class AiGenerator extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Json
     */
    private $jsonSerializer;

    public function __construct(
        Context $context,
        Registry $registry,
        Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Current product instance
     */
    public function getProduct(): ?ProductInterface
    {
        /** @var ProductInterface|null $product */
        $product = $this->registry->registry('current_product');
        return $product;
    }

    public function getProductId(): int
    {
        return (int)($this->getProduct()?->getId() ?? 0);
    }

    public function getProductName(): string
    {
        return (string)($this->getProduct()?->getName() ?? '');
    }

    public function getMetaTitle(): string
    {
        return (string)($this->getProduct()?->getMetaTitle() ?? '');
    }

    public function getMetaDescription(): string
    {
        return (string)($this->getProduct()?->getMetaDescription() ?? '');
    }

    public function getMetaKeywords(): string
    {
        return (string)($this->getProduct()?->getMetaKeyword() ?? '');
    }

    /**
     * Provide JS config for modal initialization
     */
    public function getJsonConfig(): string
    {
        $config = [
            'productId' => $this->getProductId(),
            'productName' => $this->getProductName(),
            'metaTitle' => $this->getMetaTitle(),
            'metaKeywords' => $this->getMetaKeywords(),
            'metaDescription' => $this->getMetaDescription()
        ];

        return $this->jsonSerializer->serialize($config);
    }
}

