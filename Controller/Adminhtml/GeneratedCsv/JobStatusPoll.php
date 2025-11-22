<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Helper\FileManager;
use Squadkin\SquadexaAI\Service\SquadexaApiService;

/**
 * Class JobStatusPoll
 *
 * Handles polling job status and downloading results when completed
 * Called by JavaScript every 10 seconds
 */
class JobStatusPoll extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::squadkin_squadexaai_generatedcsv_view';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param SquadexaApiService $apiService
     * @param FileManager $fileManager
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        SquadexaApiService $apiService,
        FileManager $fileManager,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->apiService = $apiService;
        $this->fileManager = $fileManager;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    /**
     * Execute job status polling
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $generatedCsvId = (int)$this->getRequest()->getParam('generatedcsv_id');
            
            if (!$generatedCsvId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Generated CSV ID is required.')
                ]);
            }

            // Load the GeneratedCsv record
            $generatedCsv = $this->generatedCsvRepository->get($generatedCsvId);
            $jobId = $generatedCsv->getJobId();

            if (!$jobId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Job ID not found for this record.'),
                    'status' => 'error'
                ]);
            }

            // Check job status via API
            $jobStatusResponse = $this->apiService->getJobStatus($jobId);
            
            $rawStatus = $jobStatusResponse['status'] ?? 'pending';
            $progress = $jobStatusResponse['progress'] ?? 0;
            $completed = $jobStatusResponse['completed'] ?? 0;
            $total = $jobStatusResponse['total'] ?? 0;
            
            // Normalize status values from API to our standard values
            $statusMap = [
                'pending' => 'pending',
                'in_progress' => 'processing',
                'processing' => 'processing',
                'completed' => 'completed',
                'failed' => 'failed',
                'error' => 'failed'
            ];
            $status = $statusMap[$rawStatus] ?? 'pending';
            
            $this->logger->info('SquadexaAI JobStatusPoll: Job status checked', [
                'generatedcsv_id' => $generatedCsvId,
                'job_id' => $jobId,
                'raw_status' => $rawStatus,
                'normalized_status' => $status,
                'progress' => $progress,
                'completed' => $completed,
                'total' => $total
            ]);

            // Update status in database if it changed
            $currentStatus = $generatedCsv->getImportStatus() ?? 'pending';
            $statusChanged = ($status !== $currentStatus);
            
            if ($statusChanged) {
                $generatedCsv->setImportStatus($status);
                
                // If status is completed, download the file
                if ($status === 'completed') {
                    try {
                        // Download JSON response from API
                        $jsonContent = $this->apiService->downloadJobResults($jobId);
                        
                        // Parse JSON response
                        $jsonData = json_decode($jsonContent, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new LocalizedException(__('Invalid JSON response from API: %1', json_last_error_msg()));
                        }
                        
                        // Extract products array from response
                        $products = $jsonData['products'] ?? [];
                        if (empty($products)) {
                            throw new LocalizedException(__('No products found in API response'));
                        }
                        
                        // Generate output CSV file from JSON data
                        $inputFileName = $generatedCsv->getInputFileName();
                        $pathInfo = pathinfo($inputFileName);
                        $timestamp = date('Y-m-d_H-i-s');
                        $randomString = substr(md5(uniqid()), 0, 8);
                        $outputFileName = 'ai_generated_' . $pathInfo['filename'] . '_' . $timestamp . '_' . $randomString . '.csv';
                        
                        // Convert JSON products to CSV format
                        $csvContent = $this->convertJsonToCsv($products);
                        
                        // Save CSV content to file
                        $this->fileManager->createDirectories();
                        $varDirectory = $this->directoryList->getPath('var');
                        $outputDir = $varDirectory . '/AIProductCreator/Output';
                        $outputFilePath = $outputDir . '/' . $outputFileName;
                        
                        // Ensure directory exists
                        if (!is_dir($outputDir)) {
                            mkdir($outputDir, 0755, true);
                        }
                        
                        // Write CSV content to file
                        file_put_contents($outputFilePath, $csvContent);
                        
                        $this->logger->info('SquadexaAI JobStatusPoll: Response file saved', [
                            'output_file_name' => $outputFileName,
                            'output_file_path' => $outputFilePath,
                            'file_size' => strlen($csvContent),
                            'products_count' => count($products)
                        ]);
                        
                        // Update database with file information
                        $generatedCsv->setResponseFileName($outputFileName);
                        $generatedCsv->setResponseFilePath('/var/AIProductCreator/Output/' . $outputFileName);
                        $generatedCsv->setTotalProductsCount(count($products));
                        
                        // Save products to AI Product table
                        $saveResult = $this->fileManager->saveAiProductData($products, $generatedCsvId, 'csv');
                        // Log update statistics for CSV processing
                        $this->logger->info('SquadexaAI JobStatusPoll: Products saved', [
                            'total' => $saveResult['total_saved'],
                            'created' => $saveResult['created_count'],
                            'updated' => $saveResult['updated_count']
                        ]);
                        
                    } catch (\Exception $e) {
                        $this->logger->error('SquadexaAI JobStatusPoll: Error downloading job results', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        $generatedCsv->setImportStatus('failed');
                        $generatedCsv->setImportErrorMessage('Failed to download job results: ' . $e->getMessage());
                    }
                } elseif ($status === 'failed') {
                    $errorMessage = $jobStatusResponse['error'] ?? 'Job processing failed';
                    $generatedCsv->setImportStatus('failed');
                    $generatedCsv->setImportErrorMessage($errorMessage);
                }
            }
            
            // Always save to ensure database is updated
            $this->generatedCsvRepository->save($generatedCsv);

            return $result->setData([
                'success' => true,
                'status' => $status,
                'progress' => $progress,
                'completed' => $completed,
                'total' => $total,
                'refresh_grid' => ($status === 'completed')
            ]);

        } catch (NoSuchEntityException $e) {
            $this->logger->error('SquadexaAI JobStatusPoll: Record not found', [
                'error' => $e->getMessage()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => __('Record not found.'),
                'status' => 'error'
            ]);
        } catch (LocalizedException $e) {
            $this->logger->error('SquadexaAI JobStatusPoll: Localized exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 'error'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI JobStatusPoll: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while checking job status: %1', $e->getMessage()),
                'status' => 'error'
            ]);
        }
    }

    /**
     * Convert JSON products array to CSV format
     *
     * @param array $products
     * @return string CSV content
     */
    private function convertJsonToCsv(array $products): string
    {
        if (empty($products)) {
            return '';
        }

        // Define CSV headers based on the new structure
        $headers = [
            'product_name',
            'primary_keywords',
            'secondary_keywords',
            'include_pricing',
            'meta_title',
            'meta_description',
            'short_description',
            'description',
            'key_features',
            'how_to_use',
            'ingredients',
            'upc',
            'keywords',
            'pricing USD min',
            'pricing USD max',
            'pricing CAD min',
            'pricing CAD max'
        ];

        // Create output buffer
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $headers, ',', '"', '\\');

        // Write product data rows
        foreach ($products as $product) {
            $row = [];
            
            // Extract each field
            $row[] = $product['product_name'] ?? '';
            $row[] = is_array($product['primary_keywords'] ?? null) 
                ? implode(',', $product['primary_keywords']) 
                : ($product['primary_keywords'] ?? '');
            $row[] = is_array($product['secondary_keywords'] ?? null) 
                ? implode(',', $product['secondary_keywords']) 
                : ($product['secondary_keywords'] ?? '');
            $row[] = isset($product['include_pricing']) ? ($product['include_pricing'] ? 'true' : 'false') : '';
            $row[] = $product['meta_title'] ?? '';
            $row[] = $product['meta_description'] ?? '';
            $row[] = $product['short_description'] ?? '';
            $row[] = $product['description'] ?? '';
            $row[] = is_array($product['key_features'] ?? null) 
                ? implode('|', $product['key_features']) 
                : ($product['key_features'] ?? '');
            $row[] = is_array($product['how_to_use'] ?? null) 
                ? implode('|', $product['how_to_use']) 
                : ($product['how_to_use'] ?? '');
            $row[] = is_array($product['ingredients'] ?? null) 
                ? implode('|', $product['ingredients']) 
                : ($product['ingredients'] ?? '');
            $row[] = $product['upc'] ?? '';
            $row[] = is_array($product['keywords'] ?? null) 
                ? implode(',', $product['keywords']) 
                : ($product['keywords'] ?? '');
            
            // Extract pricing
            $pricing = $product['pricing'] ?? [];
            $row[] = $pricing['USD']['min_price'] ?? '';
            $row[] = $pricing['USD']['max_price'] ?? '';
            $row[] = $pricing['CAD']['min_price'] ?? '';
            $row[] = $pricing['CAD']['max_price'] ?? '';
            
            fputcsv($output, $row, ',', '"', '\\');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}

