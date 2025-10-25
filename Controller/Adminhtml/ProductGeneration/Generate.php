<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\ProductGeneration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Squadkin\SquadexaAI\Logger\Logger as SquadexaLogger;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\SquadexaAI\Helper\FileManager;
use Psr\Log\LoggerInterface;

class Generate extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var SquadexaApiService
     */
    protected $apiService;

    /**
     * @var SquadexaLogger
     */
    protected $squadexaLogger;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    protected $generatedCsvRepository;

    /**
     * @var GeneratedCsvInterfaceFactory
     */
    protected $generatedCsvFactory;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param SquadexaApiService $apiService
     * @param SquadexaLogger $squadexaLogger
     * @param LoggerInterface $logger
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param FileManager $fileManager
     * @param Json $jsonSerializer
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        SquadexaApiService $apiService,
        SquadexaLogger $squadexaLogger,
        LoggerInterface $logger,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        FileManager $fileManager,
        Json $jsonSerializer
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->apiService = $apiService;
        $this->squadexaLogger = $squadexaLogger;
        $this->logger = $logger;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context);
    }

    /**
     * Generate single product via AJAX
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $productName = $this->getRequest()->getParam('product_name');
            $primaryKeywords = $this->getRequest()->getParam('primary_keywords');
            $secondaryKeywords = $this->getRequest()->getParam('secondary_keywords', '');
            $includePricing = $this->getRequest()->getParam('include_pricing', false);

            // Validate required fields
            if (empty($productName) || empty($primaryKeywords)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product name and primary keywords are required.')
                ]);
            }

            $this->squadexaLogger->logApiRequest('Single product generation', [
                'product_name' => $productName,
                'primary_keywords' => $primaryKeywords,
                'secondary_keywords' => $secondaryKeywords,
                'include_pricing' => $includePricing
            ]);

            // Prepare data for API
            $productData = [
                'product_name' => $productName,
                'primary_keywords' => explode(',', $primaryKeywords),
                'secondary_keywords' => !empty($secondaryKeywords) ? explode(',', $secondaryKeywords) : [],
                'include_pricing' => (bool)$includePricing
            ];

            // Call API to generate product
            $apiResponse = $this->apiService->generateProduct($productData);

            $this->squadexaLogger->logApiResponse('Single product generation response', [
                'response_data' => $apiResponse
            ]);

            // Step 1: Save to GeneratedCsv table
            $generatedCsv = $this->generatedCsvFactory->create();
            $generatedCsv->setInputFileName('Single Product: ' . $productName);
            $generatedCsv->setInputFilePath('N/A - Single Product Generation');
            $generatedCsv->setResponseFileName('N/A - API Response');
            $generatedCsv->setResponseFilePath('N/A - Stored in aiproduct table');
            $generatedCsv->setTotalProductsCount(1);
            $generatedCsv->setGenerationType('single');
            $generatedCsv->setImportStatus('pending');
            
            $this->generatedCsvRepository->save($generatedCsv);
            $generatedCsvId = $generatedCsv->getGeneratedcsvId();

            // Step 2: Save to AiProduct table using FileManager
            $productArray = [[
                'sku' => $apiResponse['sku'] ?? 'AUTO-' . time(),
                'name' => $apiResponse['name'] ?? $productName,
                'description' => $apiResponse['description'] ?? '',
                'short_description' => $apiResponse['short_description'] ?? '',
                'price' => $apiResponse['price'] ?? 0,
                'primary_keywords' => $primaryKeywords,
                'secondary_keywords' => $secondaryKeywords,
                'ai_response' => $this->jsonSerializer->serialize($apiResponse)
            ]];
            
            $this->fileManager->saveAiProductData($productArray, $generatedCsvId, 'single');

            return $result->setData([
                'success' => true,
                'message' => __('Product generated successfully! Saved to database with ID: %1', $generatedCsvId),
                'data' => $apiResponse,
                'csv_id' => $generatedCsvId
            ]);

        } catch (LocalizedException $e) {
            $this->logger->error('SquadexaAI Single Product Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Single Product Generation Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while generating the product: %1', $e->getMessage())
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

