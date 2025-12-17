<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Plugin\Catalog\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to store AI product ID in product before save
 */
class ProductRepositoryPlugin
{
    /**
     * Custom data key to store AI product ID in product object
     */
    public const AI_PRODUCT_ID_KEY = 'ai_product_id';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Store AI product ID in product before save
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @param bool $saveOptions
     * @return array
     */
    public function beforeSave(
        ProductRepositoryInterface $subject,
        ProductInterface $product,
        $saveOptions = false
    ): array {
        $productId = $product->getId();

        // Only for new products (no ID or ID is 0)
        if (!$productId || $productId == 0) {
            $this->processAiProductId($product);
        }
        return [$product, $saveOptions];
    }

    /**
     * Process AI product ID for new products
     *
     * @param ProductInterface $product
     * @return void
     */
    private function processAiProductId(ProductInterface $product)
    {
        $existingAiProductId = $product->getData(self::AI_PRODUCT_ID_KEY);

        // Check if AI product ID is already in product data
        if (!$existingAiProductId) {
            $aiProductId = $this->getAiProductIdFromRequest();

            if ($aiProductId) {
                if ($aiProductId > 0) {
                    $product->setData(self::AI_PRODUCT_ID_KEY, $aiProductId);
                } else {
                    $this->logger->warning(
                        'ProductRepositoryPlugin: Invalid AI product ID detected before save',
                        ['ai_product_id' => $aiProductId]
                    );
                }
            }
        }
    }

    /**
     * Get AI Product ID from request
     *
     * @return int|null
     */
    private function getAiProductIdFromRequest()
    {
        $aiProductId = null;
        
        // Method 1: Check URL parameters
        $urlParam = $this->request->getParam('ai_data');
        if ($urlParam) {
            return (int)$urlParam;
        }

        // Method 2: Check POST data
        if ($this->request->isPost()) {
            $postData = $this->request->getPostValue();
            
            // Check direct POST keys
            $aiProductId = $postData['ai_data'] ?? $postData[self::AI_PRODUCT_ID_KEY] ?? null;
            
            if (!$aiProductId && isset($postData['product'])) {
                $productData = $postData['product'];
                $aiProductId = $productData['ai_data'] ?? $productData[self::AI_PRODUCT_ID_KEY] ?? null;
                
                if (!$aiProductId) {
                    $aiProductId = $this->findAiIdInGeneral($productData);
                }
            }
        }

        return $aiProductId ? (int)$aiProductId : null;
    }

    /**
     * Find AI ID in general sections
     *
     * @param array $productData
     * @return mixed|null
     */
    private function findAiIdInGeneral(array $productData)
    {
        $generalCandidates = [];
        if (isset($productData['general']) && is_array($productData['general'])) {
            $generalCandidates[] = $productData['general'];
        }
        if (isset($productData['product']['general']) && is_array($productData['product']['general'])) {
            $generalCandidates[] = $productData['product']['general'];
        }
        
        foreach ($generalCandidates as $generalData) {
            $aiProductId = $generalData['ai_data'] ?? $generalData[self::AI_PRODUCT_ID_KEY] ?? null;
            if ($aiProductId) {
                return $aiProductId;
            }
        }
        return null;
    }
}
