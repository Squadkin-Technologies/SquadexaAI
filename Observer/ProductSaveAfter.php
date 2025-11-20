<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Observer to update AI product record when Magento product is saved
 */
class ProductSaveAfter implements ObserverInterface
{
    /**
     * Custom data key to store AI product ID in product object
     */
    const AI_PRODUCT_ID_KEY = 'ai_product_id';

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        AiProductRepositoryInterface $aiProductRepository,
        LoggerInterface $logger,
        RequestInterface $request
    ) {
        $this->aiProductRepository = $aiProductRepository;
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var ProductInterface $product */
            $product = $observer->getEvent()->getData('product');
            
            if (!$product || !$product->getId()) {
                return;
            }

            $magentoProductId = (int)$product->getId();
            
            // Method 1: Check if AI product ID is stored in product data (from AiDataModifier or Plugin)
            $aiProductId = $product->getData(self::AI_PRODUCT_ID_KEY);

            // Method 2: Check request parameters (ai_data can be in GET or POST)
            if (!$aiProductId) {
                $aiProductId = $this->request->getParam('ai_data');
            }
            
            // Method 3: Check POST data (form submission) - UI component forms use nested structure
            if (!$aiProductId && $this->request->isPost()) {
                $postData = $this->request->getPostValue();
                
                // Check direct POST keys
                $aiProductId = $postData['ai_data'] ?? $postData[self::AI_PRODUCT_ID_KEY] ?? null;
                
                // Check nested in 'product' array (UI component form structure)
                if (!$aiProductId && isset($postData['product'])) {
                    $productData = $postData['product'];
                    $aiProductId = $productData['ai_data'] ?? $productData[self::AI_PRODUCT_ID_KEY] ?? null;
                    
                    if (!$aiProductId && isset($productData['product'])) {
                        $nestedProduct = $productData['product'];
                        $aiProductId = $nestedProduct['ai_data'] ?? $nestedProduct[self::AI_PRODUCT_ID_KEY] ?? null;
                    }
                }
                
                // Check in general sections (top-level general and nested product.general)
                if (!$aiProductId) {
                    $generalCandidates = [];
                    if (isset($postData['product']['general']) && is_array($postData['product']['general'])) {
                        $generalCandidates[] = [
                            'path' => 'product.general',
                            'data' => $postData['product']['general']
                        ];
                    }
                    if (isset($postData['product']['product']['general']) && is_array($postData['product']['product']['general'])) {
                        $generalCandidates[] = [
                            'path' => 'product.product.general',
                            'data' => $postData['product']['product']['general']
                        ];
                    }
                    
                    foreach ($generalCandidates as $candidate) {
                        $generalData = $candidate['data'];
                        $aiProductId = $generalData['ai_data'] ?? $generalData[self::AI_PRODUCT_ID_KEY] ?? null;
                        if ($aiProductId) {
                            break;
                        }
                    }
                }
            }
            
            // Method 4: Check product object data (from extension attributes or custom data)
            if (!$aiProductId) {
                $productData = $product->getData();
                $aiProductId = $productData[self::AI_PRODUCT_ID_KEY] ?? null;
            }

            if (!$aiProductId) {
                return;
            }

            $aiProductId = (int)$aiProductId;
            
            if ($aiProductId <= 0) {
                $this->logger->warning('ProductSaveAfter: Invalid AI product ID', [
                    'ai_product_id' => $aiProductId,
                    'product_id' => $magentoProductId
                ]);
                return;
            }

            // Get AI product
            try {
                $aiProduct = $this->aiProductRepository->get($aiProductId);
            } catch (NoSuchEntityException $e) {
                $this->logger->error('ProductSaveAfter: AI product not found', [
                    'ai_product_id' => $aiProductId,
                    'magento_product_id' => $magentoProductId,
                    'error' => $e->getMessage()
                ]);
                return;
            }

            // Check if AI product is already linked to a different Magento product
            $existingMagentoProductId = $aiProduct->getMagentoProductId();
            if ($existingMagentoProductId && (int)$existingMagentoProductId !== $magentoProductId) {
                // Update anyway - the new product takes precedence
            }

            // Update AI product record
            $aiProduct->setMagentoProductId($magentoProductId);
            $aiProduct->setIsCreatedInMagento(true);
            
            $this->aiProductRepository->save($aiProduct);

        } catch (LocalizedException $e) {
            $this->logger->error('ProductSaveAfter: Localized exception while updating AI product', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ProductSaveAfter: Exception while updating AI product', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

