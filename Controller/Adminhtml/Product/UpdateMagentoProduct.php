<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Service\AiFieldMappingService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class UpdateMagentoProduct extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var AiProductRepositoryInterface
     */
    protected $aiProductRepository;

    /**
     * @var AiFieldMappingService
     */
    protected $mappingService;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiFieldMappingService $mappingService
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        AiProductRepositoryInterface $aiProductRepository,
        AiFieldMappingService $mappingService,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->aiProductRepository = $aiProductRepository;
        $this->mappingService = $mappingService;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Create exception in case CSRF validation failed.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // Allow AJAX requests without strict CSRF validation
        if ($request->isXmlHttpRequest() || $request->getParam('isAjax')) {
            return true;
        }
        return null; // Use default validation for non-AJAX requests
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $aiproductId = (int)$this->getRequest()->getParam('aiproduct_id');
            $productType = $this->getRequest()->getParam('product_type', 'simple');

            $this->logger->info('UpdateMagentoProduct: Starting update', [
                'aiproduct_id' => $aiproductId,
                'product_type' => $productType
            ]);

            if (!$aiproductId) {
                throw new LocalizedException(__('AI Product ID is required.'));
            }

            // Get AI Product
            try {
                $aiProduct = $this->aiProductRepository->get($aiproductId);
                $this->logger->info('UpdateMagentoProduct: AI Product loaded', [
                    'aiproduct_id' => $aiproductId,
                    'product_name' => $aiProduct->getProductName(),
                    'is_created_in_magento' => $aiProduct->getIsCreatedInMagento(),
                    'magento_product_id' => $aiProduct->getMagentoProductId()
                ]);
            } catch (\Exception $e) {
                $this->logger->error('UpdateMagentoProduct: Failed to load AI product', [
                    'aiproduct_id' => $aiproductId,
                    'error' => $e->getMessage()
                ]);
                throw new LocalizedException(__('Failed to load AI product: %1', $e->getMessage()));
            }
            
            // Check if product is created in Magento
            if (!$aiProduct->getIsCreatedInMagento() || !$aiProduct->getMagentoProductId()) {
                throw new LocalizedException(__('This AI product has not been created in Magento yet. Please create it first.'));
            }

            $magentoProductId = (int)$aiProduct->getMagentoProductId();

            // Get Magento Product in edit mode (required for admin updates)
            try {
                $magentoProduct = $this->productRepository->getById($magentoProductId, true);
                $this->logger->info('UpdateMagentoProduct: Magento Product loaded', [
                    'magento_product_id' => $magentoProductId,
                    'product_name' => $magentoProduct->getName(),
                    'sku' => $magentoProduct->getSku()
                ]);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->error('UpdateMagentoProduct: Magento product not found', [
                    'magento_product_id' => $magentoProductId,
                    'error' => $e->getMessage()
                ]);
                throw new LocalizedException(__('Magento product (ID: %1) does not exist. It may have been deleted.', $magentoProductId));
            } catch (\Exception $e) {
                $this->logger->error('UpdateMagentoProduct: Failed to load Magento product', [
                    'magento_product_id' => $magentoProductId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new LocalizedException(__('Failed to load Magento product (ID: %1): %2', $magentoProductId, $e->getMessage()));
            }

            // Validate updated_at: AI product must be newer than Magento product
            $aiUpdatedAt = $aiProduct->getUpdatedAt();
            $magentoUpdatedAt = $magentoProduct->getUpdatedAt();
            
            $this->logger->info('UpdateMagentoProduct: Comparing timestamps', [
                'ai_updated_at' => $aiUpdatedAt,
                'magento_updated_at' => $magentoUpdatedAt
            ]);
            
            if ($aiUpdatedAt && $magentoUpdatedAt) {
                $aiTimestamp = strtotime($aiUpdatedAt);
                $magentoTimestamp = strtotime($magentoUpdatedAt);
                
                $this->logger->info('UpdateMagentoProduct: Timestamp comparison', [
                    'ai_timestamp' => $aiTimestamp,
                    'magento_timestamp' => $magentoTimestamp,
                    'ai_is_newer' => ($aiTimestamp > $magentoTimestamp)
                ]);
                
                if ($aiTimestamp <= $magentoTimestamp) {
                    throw new LocalizedException(__(
                        'Cannot update: The AI product data was last updated on %1, but the Magento product was last updated on %2. ' .
                        'The AI product data must be newer than the Magento product to perform an update. ' .
                        'Please regenerate the AI product data first.',
                        $aiUpdatedAt,
                        $magentoUpdatedAt
                    ));
                }
            }

            // Update product using field mapping service
            $this->logger->info('UpdateMagentoProduct: Calling updateProductFromAi', [
                'magento_product_id' => $magentoProductId,
                'ai_product_id' => $aiproductId
            ]);
            
            try {
                $product = $this->mappingService->updateProductFromAi(
                    $magentoProductId,
                    $aiproductId
                );
                $this->logger->info('UpdateMagentoProduct: updateProductFromAi completed successfully');
            } catch (\Exception $e) {
                $this->logger->error('UpdateMagentoProduct: updateProductFromAi failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Save the updated product
            $this->logger->info('UpdateMagentoProduct: Saving product', [
                'magento_product_id' => $magentoProductId
            ]);
            
            try {
                $this->productRepository->save($product);
                $this->logger->info('UpdateMagentoProduct: Product saved successfully');
            } catch (\Exception $e) {
                $this->logger->error('UpdateMagentoProduct: Failed to save product', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new LocalizedException(__('Failed to save product: %1', $e->getMessage()), $e);
            }

            $this->logger->info('UpdateMagentoProduct: Update completed successfully', [
                'magento_product_id' => $magentoProductId,
                'ai_product_id' => $aiproductId
            ]);

            return $result->setData([
                'success' => true,
                'message' => __('Product has been updated successfully with the latest AI-generated data.')
            ]);

        } catch (LocalizedException $e) {
            $this->logger->error('UpdateMagentoProduct: LocalizedException', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('UpdateMagentoProduct: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while updating the product: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user has permission
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::squadexaiproductcreator');
    }
}

