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

            // Step 1: Create input reference CSV file for single product
            $inputFileName = 'input_single_' . time() . '_' . preg_replace('/[^a-z0-9]/i', '_', $productName) . '.csv';
            $inputFilePath = $this->fileManager->createSingleProductInputFile([
                'product_name' => $productName,
                'primary_keywords' => $primaryKeywords,
                'secondary_keywords' => $secondaryKeywords,
                'include_pricing' => $includePricing
            ], $inputFileName);
            
            // Step 2: Save to GeneratedCsv table (will update with response file later)
            $generatedCsv = $this->generatedCsvFactory->create();
            $generatedCsv->setInputFileName($inputFileName);
            $generatedCsv->setInputFilePath($inputFilePath);
            $generatedCsv->setResponseFileName(''); // Will be set after creating response file
            $generatedCsv->setResponseFilePath('');
            $generatedCsv->setTotalProductsCount(1);
            $generatedCsv->setGenerationType('single');
            $generatedCsv->setImportStatus('pending');
            
            $this->generatedCsvRepository->save($generatedCsv);
            $generatedCsvId = $generatedCsv->getGeneratedcsvId();

            // Step 3: Create response/output CSV file
            $responseFileName = 'response_single_' . time() . '_' . preg_replace('/[^a-z0-9]/i', '_', $productName) . '.csv';
            $responseFilePath = $this->fileManager->createSingleProductResponseFile($apiResponse, $responseFileName);
            
            // Step 4: Update GeneratedCsv with response file info
            $generatedCsv->setResponseFileName($responseFileName);
            $generatedCsv->setResponseFilePath($responseFilePath);
            $this->generatedCsvRepository->save($generatedCsv);
            
            // Step 5: Save to AiProduct table using FileManager
            // Pass full API response - FileManager will extract all fields
            $productArray = [$apiResponse];
            
            // Always set product_name from form data (required field, API response doesn't include it)
            $productArray[0]['product_name'] = $productName;
            
            // Also check for 'name' field as fallback if product_name is empty
            if (empty($productArray[0]['product_name']) && isset($productArray[0]['name'])) {
                $productArray[0]['product_name'] = $productArray[0]['name'];
            }
            
            // Add keywords from form if not in response
            if (!isset($productArray[0]['primary_keywords']) || empty($productArray[0]['primary_keywords'])) {
                $productArray[0]['primary_keywords'] = $primaryKeywords;
            }
            if (!isset($productArray[0]['secondary_keywords']) || empty($productArray[0]['secondary_keywords'])) {
                $productArray[0]['secondary_keywords'] = $secondaryKeywords;
            }
            
            $this->fileManager->saveAiProductData($productArray, $generatedCsvId, 'single');

            // Determine redirect URL based on generation type
            $generationType = $generatedCsv->getGenerationType();
            if ($generationType === 'single') {
                // Redirect to AI Generated Products grid for single product generation
                $redirectUrl = $this->getUrl('squadkin_squadexaai/aiproduct/index');
            } else {
                // Redirect to Generated Files grid for CSV generation
                $redirectUrl = $this->getUrl('squadkin_squadexaai/generatedcsv/index');
            }

            return $result->setData([
                'success' => true,
                'message' => __('Product generated successfully! Saved to database with ID: %1', $generatedCsvId),
                'data' => $apiResponse,
                'csv_id' => $generatedCsvId,
                'redirect_url' => $redirectUrl
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

