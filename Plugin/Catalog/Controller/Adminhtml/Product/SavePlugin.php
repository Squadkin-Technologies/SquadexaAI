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
 * Plugin to redirect to AI Generated Products grid after saving product created from AI
 */
class SavePlugin
{
    /**
     * Custom data key to store AI product ID
     */
    const AI_PRODUCT_ID_KEY = 'ai_product_id';

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
     * Check if product was created from AI and redirect to AI Generated Products grid
     *
     * @param Save $subject
     * @param Redirect $result
     * @return Redirect
     */
    public function afterExecute(Save $subject, Redirect $result)
    {
        try {
            // Check if this product was created from AI data
            $aiProductId = null;
            $source = null;
            
            // Method 1: Check URL parameters (ai_data can be in the URL when form loads)
            $urlParam = $this->request->getParam('ai_data');
            
            if ($urlParam) {
                $aiProductId = $urlParam;
                $source = 'url_param';
            }
            
            // Method 2: Check POST data (form submission)
            if (!$aiProductId && $this->request->isPost()) {
                $postData = $this->request->getPostValue();
                
                // Check direct POST keys
                $aiProductId = $postData['ai_data'] ?? $postData[self::AI_PRODUCT_ID_KEY] ?? null;
                if ($aiProductId) {
                    $source = 'post_data_direct';
                }
                
                // Check nested in 'product' array (UI component form structure)
                if (!$aiProductId && isset($postData['product'])) {
                    $productData = $postData['product'];
                    
                    $aiProductId = $productData['ai_data'] ?? $productData[self::AI_PRODUCT_ID_KEY] ?? null;
                    if ($aiProductId) {
                        $source = 'post_data_product';
                    }
                    
                    // Check in general sections (can exist at multiple paths)
                    if (!$aiProductId) {
                        $generalCandidates = [];
                        if (isset($productData['general']) && is_array($productData['general'])) {
                            $generalCandidates[] = [
                                'path' => 'product.general',
                                'data' => $productData['general']
                            ];
                        }
                        if (isset($productData['product']['general']) && is_array($productData['product']['general'])) {
                            $generalCandidates[] = [
                                'path' => 'product.product.general',
                                'data' => $productData['product']['general']
                            ];
                        }
                        
                        foreach ($generalCandidates as $candidate) {
                            $generalData = $candidate['data'];
                            $aiProductId = $generalData['ai_data'] ?? $generalData[self::AI_PRODUCT_ID_KEY] ?? null;
                            if ($aiProductId) {
                                $source = $candidate['path'];
                                break;
                            }
                        }
                    }
                }
            }

            $aiProductId = $aiProductId ? (int)$aiProductId : null;

            // If AI product ID found, redirect to AI Generated Products grid
            if ($aiProductId && $aiProductId > 0) {
                $this->messageManager->addSuccessMessage(
                    __('Squadexa AI generated product has been saved.')
                );
                $this->logger->info('SavePlugin: Redirecting to AI Generated Products grid', [
                    'ai_product_id' => $aiProductId,
                    'source' => $source
                ]);
                
                // Redirect to AI Generated Products grid
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
}

