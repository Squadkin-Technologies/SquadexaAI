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
            $existingAiProductId = $product->getData(self::AI_PRODUCT_ID_KEY);

            // Check if AI product ID is already in product data
            if (!$existingAiProductId) {
                $aiProductId = null;

                // Try to get from request URL parameter
                $urlParam = $this->request->getParam('ai_data');
                $aiProductId = $urlParam ?: null;

                // Check POST data (UI component forms use nested structure)
                if (!$aiProductId && $this->request->isPost()) {
                    $postData = $this->request->getPostValue();

                    // Check direct POST keys
                    $aiProductId = $postData['ai_data'] ?? $postData[self::AI_PRODUCT_ID_KEY] ?? null;

                    // Check nested in 'product' array
                    if (!$aiProductId && isset($postData['product'])) {
                        $productData = $postData['product'];
                        $aiProductId = $productData['ai_data'] ?? $productData[self::AI_PRODUCT_ID_KEY] ?? null;

                        if (!$aiProductId && isset($productData['product'])) {
                            $nestedProduct = $productData['product'];
                            $aiProductId = $nestedProduct['ai_data'] ?? $nestedProduct[self::AI_PRODUCT_ID_KEY] ?? null;
                        }

                        // Check in all possible general sections
                        if (!$aiProductId) {
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
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($aiProductId) {
                    $aiProductId = (int)$aiProductId;
                    if ($aiProductId > 0) {
                        $product->setData(self::AI_PRODUCT_ID_KEY, $aiProductId);
                    } else {
                        $this->logger->warning('ProductRepositoryPlugin: Invalid AI product ID detected before save', [
                            'ai_product_id' => $aiProductId
                        ]);
                    }
                }
            }
        }
        return [$product, $saveOptions];
    }
}
