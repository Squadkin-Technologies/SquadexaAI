<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Squadkin\SquadexaAI\Helper\FileManager;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\SquadexaAI\Model\ResourceModel\AiProduct\CollectionFactory as AiProductCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Generate AI data for existing product
 */
class GenerateAiData extends Action
{
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var GeneratedCsvInterfaceFactory
     */
    private $generatedCsvFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AiProductCollectionFactory
     */
    private $aiProductCollectionFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SquadexaApiService $apiService
     * @param FileManager $fileManager
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param AiProductCollectionFactory $aiProductCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SquadexaApiService $apiService,
        FileManager $fileManager,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        AiProductCollectionFactory $aiProductCollectionFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiService = $apiService;
        $this->fileManager = $fileManager;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->aiProductCollectionFactory = $aiProductCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $productName = $this->getRequest()->getParam('product_name');
            $primaryKeywords = $this->getRequest()->getParam('primary_keywords');
            $secondaryKeywords = $this->getRequest()->getParam('secondary_keywords', '');
            $includePricing = (bool)$this->getRequest()->getParam('include_pricing', false);
            $productId = (int)$this->getRequest()->getParam('product_id', 0);

            // Validate required fields
            if (empty($productName) || empty($primaryKeywords)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product name and primary keywords are required.')
                ]);
            }

            // Prepare data for API
            $productData = [
                'product_name' => $productName,
                'primary_keywords' => explode(',', $primaryKeywords),
                'secondary_keywords' => !empty($secondaryKeywords) ? explode(',', $secondaryKeywords) : [],
                'include_pricing' => $includePricing
            ];

            // Call API to generate product
            $apiResponse = $this->apiService->generateProduct($productData);

            // For single product generation, skip CSV file creation and GeneratedCsv table entry
            // Save directly to AiProduct table
            $productArray = [$apiResponse];
            $productArray[0]['product_name'] = $productName;
            if (!isset($productArray[0]['primary_keywords']) || empty($productArray[0]['primary_keywords'])) {
                $productArray[0]['primary_keywords'] = $primaryKeywords;
            }
            if (!isset($productArray[0]['secondary_keywords']) || empty($productArray[0]['secondary_keywords'])) {
                $productArray[0]['secondary_keywords'] = $secondaryKeywords;
            }

            // Save to AiProduct table with null generatedCsvId for single products
            $this->fileManager->saveAiProductData($productArray, null, 'single');

            // Get the saved AI product ID
            $aiProductId = null;
            if (!empty($productArray)) {
                // Find the AI product we just created (by product_name and generation_type, not generatedcsv_id)
                $aiProductCollection = $this->aiProductCollectionFactory->create();
                $aiProductCollection->addFieldToFilter('product_name', $productName)
                    ->addFieldToFilter('generation_type', 'single')
                    ->setOrder('created_at', 'DESC')
                    ->setPageSize(1);
                
                if ($aiProductCollection->getSize() > 0) {
                    $aiProduct = $aiProductCollection->getFirstItem();
                    $aiProductId = $aiProduct->getAiproductId();
                }
            }

            return $result->setData([
                'success' => true,
                'message' => __('AI data generated successfully!'),
                'ai_product_id' => $aiProductId,
                'data' => $apiResponse
            ]);

        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Generate AI Data Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $result->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}

