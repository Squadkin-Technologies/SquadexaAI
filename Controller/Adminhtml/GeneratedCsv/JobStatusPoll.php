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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
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
    public const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::squadkin_squadexaai_generatedcsv_view';

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
     * @var Filesystem
     */
    private $filesystem;

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
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        SquadexaApiService $apiService,
        FileManager $fileManager,
        DirectoryList $directoryList,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->apiService = $apiService;
        $this->fileManager = $fileManager;
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
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
            
            $this->logger->info(
                'SquadexaAI JobStatusPoll: Job status checked',
                [
                    'generatedcsv_id' => $generatedCsvId,
                    'job_id' => $jobId,
                    'raw_status' => $rawStatus,
                    'normalized_status' => $status,
                    'progress' => $progress,
                    'completed' => $completed,
                    'total' => $total
                ]
            );

            // Update status in database if it changed
            $currentStatus = $generatedCsv->getImportStatus() ?? 'pending';
            $statusChanged = ($status !== $currentStatus);
            
            // Always update status to ensure it's current
            $generatedCsv->setImportStatus($status);
            
            // If status is completed, download the file (only if not already downloaded)
            if ($status === 'completed' && empty($generatedCsv->getResponseFileName())) {
                try {
                    // Download JSON response from API
                    $jsonContent = $this->apiService->downloadJobResults($jobId);
                    
                    // Parse JSON response
                    $jsonData = json_decode($jsonContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new LocalizedException(
                            __('Invalid JSON response from API: %1', json_last_error_msg())
                        );
                    }
                    
                    // Extract products array from response
                    $products = $jsonData['products'] ?? [];
                    if (empty($products)) {
                        throw new LocalizedException(__('No products found in API response'));
                    }
                    
                    // Generate output CSV file from JSON data
                    $inputFileName = $generatedCsv->getInputFileName();
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    $pathInfo = pathinfo($inputFileName); // phpcs:ignore
                    $timestamp = date('Y-m-d_H-i-s');
                    $randomString = substr(
                        hash('sha256', uniqid((string)random_int(0, PHP_INT_MAX), true)),
                        0,
                        8
                    );
                    $outputFileName = 'ai_generated_' . $pathInfo['filename'] . '_' .
                        $timestamp . '_' . $randomString . '.csv';
                    
                    // Convert JSON products to CSV format
                    $csvContent = $this->convertJsonToCsv($products);
                    
                    // Save CSV content to file
                    $this->fileManager->createDirectories();
                    $varDirectory = $this->filesystem->getDirectoryWrite('var');
                    $outputDir = 'AIProductCreator/Output';
                    $outputFilePath = $outputDir . '/' . $outputFileName;
                    
                    // Ensure directory exists
                    if (!$varDirectory->isExist($outputDir)) {
                        $varDirectory->create($outputDir);
                    }
                    
                    // Write CSV content to file
                    $varDirectory->writeFile($outputFilePath, $csvContent);
                    
                    $this->logger->info('SquadexaAI JobStatusPoll: Response file saved', [
                        'output_file_name' => $outputFileName,
                        'output_file_path' => $outputFilePath,
                        'file_size' => strlen($csvContent),
                        'products_count' => count($products)
                    ]);
                    
                    // Update database with file information BEFORE saving products
                    // This ensures response_file_name is set so grid shows correct status
                    $generatedCsv->setResponseFileName($outputFileName);
                    $generatedCsv->setResponseFilePath(
                        '/var/AIProductCreator/Output/' . $outputFileName
                    );
                    $generatedCsv->setTotalProductsCount(count($products));
                    $generatedCsv->setImportStatus('completed'); // Ensure status is set to completed
                    
                    // Save the CSV record first to update response_file_name
                    $this->generatedCsvRepository->save($generatedCsv);
                    
                    $this->logger->info('SquadexaAI JobStatusPoll: CSV record updated with response file', [
                        'response_file_name' => $outputFileName,
                        'status' => 'completed'
                    ]);
                    
                    // Save products to AI Product table (this prevents duplicates)
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
                    
                    // Only set to failed if status was not already completed
                    // Don't overwrite completed status if file download fails after completion
                    if ($currentStatus !== 'completed') {
                        $generatedCsv->setImportStatus('failed');
                    }
                    $generatedCsv->setImportErrorMessage('Failed to download job results: ' . $e->getMessage());
                }
            } elseif ($status === 'failed') {
                $errorMessage = $jobStatusResponse['error'] ?? 'Job processing failed';
                $generatedCsv->setImportStatus('failed');
                $generatedCsv->setImportErrorMessage($errorMessage);
            }
            
            // Always save to ensure database is updated with current status
            // Only save if we haven't already saved it (to avoid duplicate saves)
            if ($status !== 'completed' || empty($generatedCsv->getResponseFileName())) {
                $this->generatedCsvRepository->save($generatedCsv);
                
                $this->logger->info('SquadexaAI JobStatusPoll: Status saved to database', [
                    'generatedcsv_id' => $generatedCsvId,
                    'status' => $status,
                    'previous_status' => $currentStatus,
                    'status_changed' => $statusChanged,
                    'response_file_name' => $generatedCsv->getResponseFileName()
                ]);
            }

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
                'message' => __('Record not found.'), // phpcs:ignore
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
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $output = fopen('php://temp', 'r+'); // phpcs:ignore
        
        // Write headers
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        fputcsv($output, $headers, ',', '"', '\\'); // phpcs:ignore

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
            $row[] = isset($product['include_pricing'])
                ? ($product['include_pricing'] ? 'true' : 'false')
                : '';
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
            $row[] = $pricing['USD']['max_price'] ?? ''; // phpcs:ignore
            $row[] = $pricing['CAD']['min_price'] ?? '';
            $row[] = $pricing['CAD']['max_price'] ?? '';
            
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            fputcsv($output, $row, ',', '"', '\\'); // phpcs:ignore
        }

        rewind($output);
        try {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $csvContent = stream_get_contents($output); // phpcs:ignore
        } catch (\Exception $e) {
            $csvContent = '';
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        fclose($output); // phpcs:ignore

        return $csvContent;
    }
}
