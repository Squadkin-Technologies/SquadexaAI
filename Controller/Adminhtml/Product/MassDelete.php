<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class MassDelete extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var AiProductRepositoryInterface
     */
    protected $aiProductRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductRepositoryInterface $productRepository,
        AiProductRepositoryInterface $aiProductRepository,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->productRepository = $productRepository;
        $this->aiProductRepository = $aiProductRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Delete products from both Magento catalog and AI table
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $csvId = (int)$this->getRequest()->getParam('csv_id');
            
            if (!$csvId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Invalid CSV ID.')
                ]);
            }

            // Get AI products for this CSV
            $aiProducts = $this->getAiProductsByCsvId($csvId);
            
            if (empty($aiProducts)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('No products found.')
                ]);
            }

            $deletedCount = 0;
            $errors = [];

            foreach ($aiProducts as $aiProduct) {
                try {
                    // Delete from Magento catalog if created
                    if ($aiProduct->getIsCreatedInMagento() && $aiProduct->getMagentoProductId()) {
                        try {
                            $this->productRepository->deleteById($aiProduct->getMagentoProductId());
                            $this->logger->info('Deleted Magento product ID: ' . $aiProduct->getMagentoProductId());
                        } catch (\Exception $e) {
                            $this->logger->error('Could not delete Magento product: ' . $e->getMessage());
                            $errors[] = __('Failed to delete Magento product %1', $aiProduct->getSku());
                        }
                    }
                    
                    // Delete from AI product table
                    $this->aiProductRepository->delete($aiProduct);
                    $deletedCount++;
                    
                } catch (\Exception $e) {
                    $this->logger->error('Error deleting product: ' . $e->getMessage());
                    $errors[] = __('Failed to delete %1', $aiProduct->getSku());
                }
            }

            $message = __('%1 product(s) deleted successfully.', $deletedCount);
            if (!empty($errors)) {
                $message .= ' ' . __('Errors: %1', implode(', ', $errors));
            }

            return $result->setData([
                'success' => $deletedCount > 0,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error in mass delete: ' . $e->getMessage());
            
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Get AI products by CSV ID
     *
     * @param int $csvId
     * @return array
     */
    private function getAiProductsByCsvId(int $csvId): array
    {
        $products = [];
        $searchResults = $this->aiProductRepository->getList();
        
        if ($searchResults && $searchResults->getItems()) {
            foreach ($searchResults->getItems() as $aiProduct) {
                if ($aiProduct->getGeneratedcsvId() == $csvId) {
                    $products[] = $aiProduct;
                }
            }
        }
        
        return $products;
    }

    /**
     * Check if user has permission
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv');
    }
}

