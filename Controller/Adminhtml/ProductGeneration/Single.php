<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\ProductGeneration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Squadkin\SquadexaAI\Logger\Logger as SquadexaLogger;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\AiProductInterfaceFactory;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\SquadexaAI\Helper\FileManager;
use Psr\Log\LoggerInterface;

class Single extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

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
     * @var AiProductRepositoryInterface
     */
    protected $aiProductRepository;

    /**
     * @var AiProductInterfaceFactory
     */
    protected $aiProductFactory;

    /**
     * @var Json
     */
    protected $jsonSerializer;

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
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $jsonFactory
     * @param SquadexaApiService $apiService
     * @param SquadexaLogger $squadexaLogger
     * @param LoggerInterface $logger
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiProductInterfaceFactory $aiProductFactory
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param FileManager $fileManager
     * @param Json $jsonSerializer
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $jsonFactory,
        SquadexaApiService $apiService,
        SquadexaLogger $squadexaLogger,
        LoggerInterface $logger,
        AiProductRepositoryInterface $aiProductRepository,
        AiProductInterfaceFactory $aiProductFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        FileManager $fileManager,
        Json $jsonSerializer
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->apiService = $apiService;
        $this->squadexaLogger = $squadexaLogger;
        $this->logger = $logger;
        $this->aiProductRepository = $aiProductRepository;
        $this->aiProductFactory = $aiProductFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context);
    }

    /**
     * Index action - Display unified product generation page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__("Product Generation"));
        return $resultPage;
    }

    /**
     * Generate single product via AJAX
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function generate()
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
     * Get products grid (both single and CSV generated)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function grid()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $collection = $this->aiProductRepository->getList();
            $records = [];
            
            // Check if collection exists and has items
            if ($collection && $collection->getItems()) {
                foreach ($collection->getItems() as $item) {
                    $records[] = [
                        'id' => $item->getAiproductId(),
                        'product_name' => $item->getProductName(),
                        'primary_keywords' => $item->getPrimaryKeywords(),
                        'secondary_keywords' => $item->getSecondaryKeywords(),
                        'type' => 'Single', // Can be extended to differentiate
                        'status' => $item->getStatus(),
                        'status_text' => ucfirst($item->getStatus()),
                        'created_at' => date('M d, Y H:i', strtotime($item->getCreatedAt()))
                    ];
                }
                
                // Sort by creation date descending only if we have records
                if (!empty($records)) {
                    usort($records, function($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });
                }
            }
            
            return $result->setData([
                'success' => true,
                'data' => $records
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Products Grid Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty data instead of error when table doesn't exist or is empty
            return $result->setData([
                'success' => true,
                'data' => []
            ]);
        }
    }

    /**
     * Download single product result
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function download()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $result->setPath('*/*/index');
        
        try {
            $id = $this->getRequest()->getParam('id');
            
            if (!$id) {
                $this->messageManager->addErrorMessage(__('Product ID is required.'));
                return $result;
            }
            
            $aiProduct = $this->aiProductRepository->get($id);
            
            if (!$aiProduct->getAiproductId()) {
                $this->messageManager->addErrorMessage(__('Product not found.'));
                return $result;
            }
            
            // Create download file
            $response = $this->jsonSerializer->unserialize($aiProduct->getAiResponse());
            $filename = 'single_product_' . $aiProduct->getProductName() . '_' . date('Y-m-d_H-i-s') . '.json';
            
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $this->getResponse()->setBody($this->jsonSerializer->serialize($response));
            
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Single Product Download Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->messageManager->addErrorMessage(__('Error downloading product: %1', $e->getMessage()));
        }
        
        return $result;
    }

    /**
     * Delete single product result
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function delete()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $id = $this->getRequest()->getParam('id');
            
            if (!$id) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product ID is required.')
                ]);
            }
            
            $aiProduct = $this->aiProductRepository->get($id);
            
            if (!$aiProduct->getAiproductId()) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product not found.')
                ]);
            }
            
            $this->aiProductRepository->delete($aiProduct);
            
            return $result->setData([
                'success' => true,
                'message' => __('Product deleted successfully.')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Single Product Delete Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => __('Error deleting product: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::product_generation');
    }
}
