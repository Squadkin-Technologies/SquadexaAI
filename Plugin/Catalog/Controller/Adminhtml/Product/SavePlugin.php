<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Plugin\Catalog\Controller\Adminhtml\Product;

use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to redirect to Squadexa AI - Products Data grid after saving product created from AI
 */
class SavePlugin
{
    /**
     * Custom data key to store AI product ID
     */
    public const AI_PRODUCT_ID_KEY = 'ai_product_id';

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        RequestInterface $request,
        LoggerInterface $logger,
        ManagerInterface $messageManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    /**
     * Check if product was created from AI and redirect to Squadexa AI - Products Data grid
     *
     * @param Save $subject
     * @param Redirect $result
     * @return Redirect
     */
    public function afterExecute(Save $subject, Redirect $result)
    {
        try {
            // Check if this product was created from AI data
            $aiProductId = $this->getAiProductIdFromRequest();

            // If AI product ID found, redirect to Squadexa AI - Products Data grid
            if ($aiProductId && $aiProductId > 0) {
                $this->messageManager->addSuccessMessage(
                    __('Squadexa AI generated product has been saved.')
                );
                $this->logger->info('SavePlugin: Redirecting to Squadexa AI - Products Data grid', [
                    'ai_product_id' => $aiProductId
                ]);
                
                // Redirect to Squadexa AI - Products Data grid
                $result->setPath('squadkin_squadexaai/aiproduct/index');
                return $result;
            }
        } catch (\Exception $e) {
            $this->logger->error('SavePlugin: Exception in afterExecute', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
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
