<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\AiProductInterfaceFactory;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Squadkin\SquadexaAI\Model\ResourceModel\AiProduct\CollectionFactory as AiProductCollectionFactory;

class FileManager extends AbstractHelper
{
    const INPUT_DIR = 'AIProductCreator/Input';
    const OUTPUT_DIR = 'AIProductCreator/Output';
    
    /**
     * @var Filesystem
     */
    private $filesystem;
    
    /**
     * @var WriteInterface
     */
    private $varDirectory;
    
    /**
     * @var Csv
     */
    private $csvProcessor;
    
    /**
     * @var Curl
     */
    private $curl;
    
    /**
     * @var Json
     */
    private $jsonSerializer;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var AiProductInterfaceFactory
     */
    private $aiProductFactory;

    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var AiProductCollectionFactory
     */
    private $aiProductCollectionFactory;

    /**
     * FileManager constructor.
     *
     * @param Context $context
     * @param Filesystem $filesystem
     * @param Csv $csvProcessor
     * @param Curl $curl
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiProductInterfaceFactory $aiProductFactory
     * @param SquadexaApiService $apiService
     * @param AiProductCollectionFactory $aiProductCollectionFactory
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        Csv $csvProcessor,
        Curl $curl,
        Json $jsonSerializer,
        LoggerInterface $logger,
        AiProductRepositoryInterface $aiProductRepository,
        AiProductInterfaceFactory $aiProductFactory,
        SquadexaApiService $apiService,
        AiProductCollectionFactory $aiProductCollectionFactory
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->csvProcessor = $csvProcessor;
        $this->curl = $curl;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->aiProductRepository = $aiProductRepository;
        $this->aiProductFactory = $aiProductFactory;
        $this->apiService = $apiService;
        $this->aiProductCollectionFactory = $aiProductCollectionFactory;
    }

    /**
     * Create required directories if they don't exist
     *
     * @return void
     * @throws FileSystemException
     */
    public function createDirectories(): void
    {
        $inputDir = self::INPUT_DIR;
        $outputDir = self::OUTPUT_DIR;
        
        if (!$this->varDirectory->isExist($inputDir)) {
            $this->varDirectory->create($inputDir);
        }
        
        if (!$this->varDirectory->isExist($outputDir)) {
            $this->varDirectory->create($outputDir);
        }
    }

    /**
     * Save uploaded file to input directory
     *
     * @param array $fileData
     * @return string
     * @throws LocalizedException
     */
    public function saveInputFile(array $fileData): string
    {
        try {
            $this->createDirectories();
            
            $fileName = $this->generateUniqueFileName($fileData['name']);
            $filePath = self::INPUT_DIR . '/' . $fileName;
            
            // Read file content
            $fileContent = file_get_contents($fileData['tmp_name']);
            
            // Save to var directory
            $this->varDirectory->writeFile($filePath, $fileContent);
            
            return $fileName;
        } catch (\Exception $e) {
            $this->logger->error('Error saving input file: ' . $e->getMessage());
            throw new LocalizedException(__('Could not save input file: %1', $e->getMessage()));
        }
    }

    /**
     * Process file with AI API
     *
     * @param string $inputFileName
     * @param array $aiOptions
     * @return array
     * @throws LocalizedException
     */
    public function processWithAI(string $inputFileName, array $aiOptions = []): array
    {
        try {
            $inputFilePath = self::INPUT_DIR . '/' . $inputFileName;
            
            $this->logger->info('SquadexaAI FileManager: Starting CSV processing', [
                'file' => $inputFileName,
                'ai_options' => $aiOptions
            ]);
            
            // Read and parse CSV file
            $csvData = $this->csvProcessor->getData($this->varDirectory->getAbsolutePath($inputFilePath));
            
            if (empty($csvData) || count($csvData) < 2) {
                throw new LocalizedException(__('CSV file is empty or invalid.'));
            }
            
            // Get headers and data rows
            $headers = array_shift($csvData);
            $products = [];
            $totalRows = count($csvData);
            
            $this->logger->info('SquadexaAI FileManager: CSV file parsed', [
                'total_rows' => $totalRows,
                'headers' => $headers
            ]);
            
            // Validate CSV structure and data
            $this->validateCsvFile($headers, $csvData);
            
            // Process each row
            $processedCount = 0;
            $errorCount = 0;
            
            foreach ($csvData as $index => $row) {
                if (empty($row[0])) {
                    $this->logger->info('SquadexaAI FileManager: Skipping empty row', ['row_index' => $index]);
                    continue; // Skip empty rows
                }
                
                $productData = array_combine($headers, $row);
                
                // Prepare data for API
                $apiData = [
                    'product_name' => $productData['product_name'] ?? '',
                    'primary_keywords' => isset($productData['primary_keywords']) ? 
                        explode(',', $productData['primary_keywords']) : [],
                    'secondary_keywords' => isset($productData['secondary_keywords']) ? 
                        explode(',', $productData['secondary_keywords']) : [],
                    'include_pricing' => isset($productData['include_pricing']) ? 
                        (bool)$productData['include_pricing'] : false
                ];
                
                $this->logger->info('SquadexaAI FileManager: Calling API for product', [
                    'row_index' => $index,
                    'product_name' => $apiData['product_name'],
                    'api_data' => $apiData
                ]);
                
                // Call API for each product
                try {
                    $apiResponse = $this->apiService->generateProduct($apiData);
                    
                    $this->logger->info('SquadexaAI FileManager: API response received', [
                        'row_index' => $index,
                        'product_name' => $apiData['product_name'],
                        'response_keys' => array_keys($apiResponse),
                        'response_data' => $apiResponse
                    ]);
                    
                    // Merge input data with API response
                    $mergedProduct = array_merge($productData, $apiResponse);
                    $products[] = $mergedProduct;
                    $processedCount++;
                    
                    $this->logger->info('SquadexaAI FileManager: Product processed successfully', [
                        'row_index' => $index,
                        'product_name' => $apiData['product_name'],
                        'merged_keys' => array_keys($mergedProduct)
                    ]);
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logger->error('SquadexaAI FileManager: Error generating product with AI', [
                        'row_index' => $index,
                        'product_name' => $apiData['product_name'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with next product
                    continue;
                }
            }
            
            $this->logger->info('SquadexaAI FileManager: CSV processing completed', [
                'total_rows' => $totalRows,
                'processed' => $processedCount,
                'errors' => $errorCount,
                'products_returned' => count($products)
            ]);
            
            return $products;
        } catch (\Exception $e) {
            $this->logger->error('Error processing file with AI: ' . $e->getMessage());
            throw new LocalizedException(__('Could not process file with AI: %1', $e->getMessage()));
        }
    }

    /**
     * Validate CSV file structure and required fields
     *
     * @param array $headers
     * @param array $dataRows
     * @return void
     * @throws LocalizedException
     */
    private function validateCsvFile(array $headers, array $dataRows): void
    {
        // Normalize headers (remove whitespace, convert to lowercase for comparison)
        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);
        
        $this->logger->info('SquadexaAI FileManager: Validating CSV file', [
            'headers' => $headers,
            'normalized_headers' => $normalizedHeaders,
            'total_rows' => count($dataRows)
        ]);
        
        // Required fields
        $requiredFields = [
            'product_name' => 'Product Name',
            'primary_keywords' => 'Primary Keywords',
            'secondary_keywords' => 'Secondary Keywords',
            'include_pricing' => 'Include Pricing'
        ];
        
        $errors = [];
        
        // Check if required headers exist
        foreach ($requiredFields as $fieldKey => $fieldLabel) {
            $found = false;
            $actualHeaderIndex = null;
            
            // Check all possible header variations
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                $normalizedField = strtolower(str_replace(['_', '-', ' '], '', $fieldKey));
                $normalizedHeaderClean = strtolower(str_replace(['_', '-', ' '], '', $headers[$index]));
                
                if ($normalizedHeaderClean === $normalizedField || 
                    $normalizedHeaderClean === strtolower(str_replace(['_', '-', ' '], '', $fieldLabel))) {
                    $found = true;
                    $actualHeaderIndex = $index;
                    break;
                }
            }
            
            if (!$found) {
                $errors[] = __('Required column "%1" is missing. Please ensure your CSV includes this column.', $fieldLabel);
            }
        }
        
        if (!empty($errors)) {
            $errorMessage = __('CSV Validation Failed:') . ' ' . implode(' ', $errors);
            $this->logger->error('SquadexaAI FileManager: CSV validation failed - missing headers', [
                'errors' => $errors,
                'headers' => $headers
            ]);
            throw new LocalizedException($errorMessage);
        }
        
        // Validate each data row
        $rowErrors = [];
        
        foreach ($dataRows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 because row 1 is header and array is 0-indexed
            
            // Skip completely empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Map row data to headers
            $productData = [];
            if (count($row) === count($headers)) {
                $productData = array_combine($headers, $row);
            } else {
                // If column count doesn't match, try to map what we can
                foreach ($headers as $index => $header) {
                    $productData[$header] = $row[$index] ?? '';
                }
            }
            
            // Normalize product data keys for comparison
            $normalizedProductData = [];
            foreach ($productData as $key => $value) {
                $normalizedKey = strtolower(str_replace(['_', '-', ' '], '', $key));
                $normalizedProductData[$normalizedKey] = trim($value ?? '');
            }
            
            // Validate required fields in each row
            $productNameValue = '';
            $primaryKeywordsValue = '';
            $secondaryKeywordsValue = '';
            $includePricingValue = '';
            
            // Find required field values (case-insensitive)
            foreach ($normalizedProductData as $key => $value) {
                $cleanKey = strtolower(str_replace(['_', '-', ' '], '', $key));
                if ($cleanKey === 'productname' || $cleanKey === 'product_name' || $cleanKey === 'product name') {
                    $productNameValue = $value;
                }
                if ($cleanKey === 'primarykeywords' || $cleanKey === 'primary_keywords' || $cleanKey === 'primary keywords') {
                    $primaryKeywordsValue = $value;
                }
                if ($cleanKey === 'secondarykeywords' || $cleanKey === 'secondary_keywords' || $cleanKey === 'secondary keywords') {
                    $secondaryKeywordsValue = $value;
                }
                if ($cleanKey === 'includepricing' || $cleanKey === 'include_pricing' || $cleanKey === 'include pricing') {
                    $includePricingValue = $value;
                }
            }
            
            // Validate product_name
            if (empty($productNameValue)) {
                $rowErrors[] = __('Row %1: "Product Name" is required and cannot be empty.', $rowNumber);
            }
            
            // Validate primary_keywords
            if (empty($primaryKeywordsValue)) {
                $rowErrors[] = __('Row %1: "Primary Keywords" is required and cannot be empty.', $rowNumber);
            }
            
            // Validate secondary_keywords
            if (empty($secondaryKeywordsValue)) {
                $rowErrors[] = __('Row %1: "Secondary Keywords" is required and cannot be empty.', $rowNumber);
            }
            
            // Validate include_pricing (should be true/false)
            if (empty($includePricingValue)) {
                $rowErrors[] = __('Row %1: "Include Pricing" is required and must be "true" or "false".', $rowNumber);
            } elseif (!in_array(strtolower($includePricingValue), ['true', 'false', '1', '0', 'yes', 'no'])) {
                $rowErrors[] = __('Row %1: "Include Pricing" must be "true" or "false".', $rowNumber);
            }
        }
        
        if (!empty($rowErrors)) {
            $errorMessage = __('CSV Data Validation Failed:') . ' ' . implode(' ', $rowErrors);
            $this->logger->error('SquadexaAI FileManager: CSV validation failed - invalid row data', [
                'errors' => $rowErrors,
                'total_rows' => count($dataRows)
            ]);
            throw new LocalizedException($errorMessage);
        }
        
        $this->logger->info('SquadexaAI FileManager: CSV validation passed', [
            'total_rows_validated' => count($dataRows)
        ]);
    }

    /**
     * Save AI response as CSV file
     *
     * @param array $data
     * @param string $inputFileName
     * @return string
     * @throws LocalizedException
     */
    public function saveOutputFile(array $data, string $inputFileName): string
    {
        try {
            $this->createDirectories();
            
            $outputFileName = $this->generateOutputFileName($inputFileName);
            $outputFilePath = self::OUTPUT_DIR . '/' . $outputFileName;
            
            $this->logger->info('SquadexaAI FileManager: Saving output file', [
                'output_file' => $outputFileName,
                'product_count' => count($data),
                'first_product_keys' => !empty($data[0]) ? array_keys($data[0]) : []
            ]);
            
            // Convert array data to CSV format
            $csvContent = $this->convertArrayToCsv($data);
            
            $this->logger->info('SquadexaAI FileManager: CSV content generated', [
                'content_length' => strlen($csvContent),
                'first_100_chars' => substr($csvContent, 0, 100)
            ]);
            
            // Save to var directory
            $this->varDirectory->writeFile($outputFilePath, $csvContent);
            
            $this->logger->info('SquadexaAI FileManager: Output file saved successfully', [
                'output_file' => $outputFileName,
                'file_path' => $outputFilePath
            ]);
            
            return $outputFileName;
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI FileManager: Error saving output file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('Could not save output file: %1', $e->getMessage()));
        }
    }

    /**
     * Get file content for download
     *
     * @param string $fileName
     * @param string $type ('input' or 'output')
     * @return string
     * @throws LocalizedException
     */
    public function getFileContent(string $fileName, string $type): string
    {
        try {
            $directory = $type === 'input' ? self::INPUT_DIR : self::OUTPUT_DIR;
            $filePath = $directory . '/' . $fileName;
            $absolutePath = $this->varDirectory->getAbsolutePath($filePath);
            
            $this->logger->info('SquadexaAI FileManager: Getting file content', [
                'file_name' => $fileName,
                'type' => $type,
                'directory' => $directory,
                'relative_path' => $filePath,
                'absolute_path' => $absolutePath
            ]);
            
            if (!$this->varDirectory->isExist($filePath)) {
                $this->logger->error('SquadexaAI FileManager: File does not exist', [
                    'file_name' => $fileName,
                    'relative_path' => $filePath,
                    'absolute_path' => $absolutePath
                ]);
                throw new LocalizedException(__('File not found: %1', $fileName));
            }
            
            $content = $this->varDirectory->readFile($filePath);
            $this->logger->info('SquadexaAI FileManager: File read successfully', [
                'file_name' => $fileName,
                'content_length' => strlen($content)
            ]);
            
            return $content;
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI FileManager: Error reading file', [
                'file_name' => $fileName,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('Could not read file: %1', $e->getMessage()));
        }
    }

    /**
     * Get absolute file path
     *
     * @param string $fileName
     * @param string $type ('input' or 'output')
     * @return string
     */
    public function getAbsoluteFilePath(string $fileName, string $type): string
    {
        $directory = $type === 'input' ? self::INPUT_DIR : self::OUTPUT_DIR;
        return $this->varDirectory->getAbsolutePath($directory . '/' . $fileName);
    }

    /**
     * Generate unique file name
     *
     * @param string $originalName
     * @return string
     */
    private function generateUniqueFileName(string $originalName): string
    {
        $pathInfo = pathinfo($originalName);
        $timestamp = date('Y-m-d_H-i-s');
        $randomString = substr(md5(uniqid()), 0, 8);
        
        // Sanitize filename: remove invalid characters and replace spaces/special chars with underscores
        $sanitizedFilename = $this->sanitizeFileName($pathInfo['filename']);
        $sanitizedExtension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : 'csv';
        
        return $sanitizedFilename . '_' . $timestamp . '_' . $randomString . '.' . $sanitizedExtension;
    }

    /**
     * Sanitize filename by removing invalid characters
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFileName(string $filename): string
    {
        // Remove invalid characters: < > : " / \ | ? * and control characters
        $filename = preg_replace('/[<>:"\/\\\|?*\x00-\x1F]/', '', $filename);
        
        // Replace spaces and other problematic characters with underscores
        $filename = preg_replace('/[\s\(\)\[\]{}]+/', '_', $filename);
        
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Remove leading/trailing underscores and dots
        $filename = trim($filename, '_.');
        
        // If filename is empty after sanitization, use a default name
        if (empty($filename)) {
            $filename = 'uploaded_file';
        }
        
        // Limit filename length to 200 characters to avoid filesystem issues
        if (strlen($filename) > 200) {
            $filename = substr($filename, 0, 200);
        }
        
        return $filename;
    }

    /**
     * Generate output file name based on input file name
     *
     * @param string $inputFileName
     * @return string
     */
    private function generateOutputFileName(string $inputFileName): string
    {
        $pathInfo = pathinfo($inputFileName);
        $sanitizedFilename = $this->sanitizeFileName($pathInfo['filename']);
        return 'ai_generated_' . $sanitizedFilename . '.csv';
    }

    /**
     * Convert array to CSV content
     *
     * @param array $data
     * @return string
     */
    private function convertArrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'w');
        
        // Check if data is associative array
        $firstRow = reset($data);
        $isAssociative = is_array($firstRow) && !empty($firstRow);
        
        if ($isAssociative) {
            // Get headers from first row
            $headers = array_keys($firstRow);
            fputcsv($output, $headers, ',', '"', '\\');
            
            // Add data rows with values in header order
            foreach ($data as $row) {
                $normalizedRow = [];
                foreach ($headers as $header) {
                    $value = $row[$header] ?? '';
                    $normalizedRow[] = $this->normalizeValueForCsv($value);
                }
                fputcsv($output, $normalizedRow, ',', '"', '\\');
            }
        } else {
            // Simple indexed array - normalize each row
        foreach ($data as $row) {
                $normalizedRow = $this->normalizeRowForCsv($row);
                fputcsv($output, $normalizedRow, ',', '"', '\\');
            }
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }
    
    /**
     * Normalize a single value for CSV output
     *
     * @param mixed $value
     * @return string
     */
    private function normalizeValueForCsv($value): string
    {
        // Handle different value types
        if (is_array($value) || is_object($value)) {
            // Convert arrays/objects to JSON
            return $this->jsonSerializer->serialize($value);
        } elseif (is_bool($value)) {
            // Convert boolean to string
            return $value ? '1' : '0';
        } elseif ($value === null) {
            // Convert null to empty string
            return '';
        } else {
            // Keep scalar values as-is, but convert to string
            return (string)$value;
        }
    }
    
    /**
     * Normalize row data for CSV output - convert arrays/objects to strings
     * Used for non-associative arrays
     *
     * @param array|mixed $row
     * @return array
     */
    private function normalizeRowForCsv($row): array
    {
        // If not an array, wrap it
        if (!is_array($row)) {
            $row = [$row];
        }
        
        $normalized = [];
        foreach ($row as $value) {
            $normalized[] = $this->normalizeValueForCsv($value);
        }
        
        return $normalized;
    }

    /**
     * Validate uploaded file
     *
     * @param array $fileData
     * @return bool
     * @throws LocalizedException
     */
    public function validateUploadedFile(array $fileData): bool
    {
        // Check if file was uploaded
        if (!isset($fileData['tmp_name']) || empty($fileData['tmp_name'])) {
            throw new LocalizedException(__('No file was uploaded.'));
        }
        
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new LocalizedException(__('File upload error: %1', $fileData['error']));
        }
        
        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($fileData['size'] > $maxSize) {
            throw new LocalizedException(__('File size exceeds maximum limit of 10MB.'));
        }
        
        // Check file extension
        $allowedExtensions = ['csv', 'xlsx'];
        $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new LocalizedException(__('Invalid file type. Only CSV and XLSX files are allowed.'));
        }
        
        return true;
    }

    /**
     * Save AI product data to database
     *
     * @param array $aiProductData
     * @param int $generatedCsvId
     * @return void
     * @throws LocalizedException
     */
    public function saveAiProductData(array $aiProductData, int $generatedCsvId, string $generationType = 'csv'): void
    {
        $this->logger->info('FileManager saveAiProductData: Starting to save ' . count($aiProductData) . ' products for CSV ID: ' . $generatedCsvId . ', Type: ' . $generationType);
        
        try {
            $savedCount = 0;
            foreach ($aiProductData as $productData) {
                $this->logger->info('FileManager saveAiProductData: Processing product', [
                    'product_data_keys' => array_keys($productData)
                ]);
                
                // Get product_name - check both 'name' (from AI) and 'product_name' (from CSV)
                $productName = $productData['name'] ?? $productData['product_name'] ?? '';
                if (!$productName) {
                    $this->logger->warning('FileManager saveAiProductData: product_name is empty, skipping product');
                    continue;
                }
                
                // For single product generation, check if a product with the same name already exists
                // If it does, update it instead of creating a duplicate to avoid unique constraint violation
                $aiProduct = null;
                if ($generationType === 'single' && $productName) {
                    $existingCollection = $this->aiProductCollectionFactory->create();
                    $existingCollection->addFieldToFilter('product_name', $productName)
                        ->addFieldToFilter('generation_type', 'single')
                        ->setPageSize(1);
                    
                    $this->logger->info('FileManager saveAiProductData: Checking for existing product', [
                        'product_name' => $productName,
                        'generation_type' => $generationType,
                        'collection_size' => $existingCollection->getSize()
                    ]);
                    
                    if ($existingCollection->getSize() > 0) {
                        $aiProduct = $existingCollection->getFirstItem();
                        $this->logger->info('FileManager saveAiProductData: Found existing product, updating instead of creating new', [
                            'aiproduct_id' => $aiProduct->getAiproductId(),
                            'product_name' => $productName
                        ]);
                    } else {
                        $this->logger->info('FileManager saveAiProductData: No existing product found, will create new one', [
                            'product_name' => $productName
                        ]);
                    }
                }
                
                // If no existing product found, create a new one
                if (!$aiProduct) {
                    $aiProduct = $this->aiProductFactory->create();
                }
                
                // Set required fields
                // For single product generation, generatedCsvId can be null
                if ($generatedCsvId !== null) {
                    $aiProduct->setGeneratedcsvId($generatedCsvId);
                }
                $aiProduct->setGenerationType($generationType);
                $aiProduct->setProductName($productName);
                
                // Set SKU if the column exists (for backward compatibility with old schema)
                // Generate a unique SKU based on product name and timestamp if not set
                // Use setData() since AbstractModel supports it for any column
                $sku = 'AI-' . preg_replace('/[^a-zA-Z0-9]/', '-', $productName) . '-' . time();
                $sku = substr($sku, 0, 64); // Limit to 64 characters
                $aiProduct->setData('sku', $sku);
                
                // Fields to extract and save separately (all optional)
                $fieldsToExtract = [
                    'product_name', 'name', 
                    'meta_title', 'meta_description', 'short_description', 'description',
                    'key_features', 'how_to_use', 'ingredients', 'upc', 'keywords',
                    'pricing'
                ];
                
                // Extract meta_title
                if (isset($productData['meta_title']) && $productData['meta_title']) {
                    $aiProduct->setMetaTitle($productData['meta_title']);
                }
                
                // Extract meta_description
                if (isset($productData['meta_description']) && $productData['meta_description']) {
                    $aiProduct->setMetaDescription($productData['meta_description']);
                }
                
                // Extract short_description
                if (isset($productData['short_description']) && $productData['short_description']) {
                    $aiProduct->setShortDescription($productData['short_description']);
                }
                
                // Extract description
                if (isset($productData['description']) && $productData['description']) {
                    $aiProduct->setDescription($productData['description']);
                }
                
                // Extract key_features (array to JSON string)
                if (isset($productData['key_features'])) {
                    $keyFeatures = is_array($productData['key_features']) 
                        ? $this->jsonSerializer->serialize($productData['key_features'])
                        : $productData['key_features'];
                    $aiProduct->setKeyFeatures($keyFeatures);
                }
                
                // Extract how_to_use (array to JSON string)
                if (isset($productData['how_to_use'])) {
                    $howToUse = is_array($productData['how_to_use']) 
                        ? $this->jsonSerializer->serialize($productData['how_to_use'])
                        : $productData['how_to_use'];
                    $aiProduct->setHowToUse($howToUse);
                }
                
                // Extract ingredients (array to JSON string)
                if (isset($productData['ingredients'])) {
                    $ingredients = is_array($productData['ingredients']) 
                        ? $this->jsonSerializer->serialize($productData['ingredients'])
                        : $productData['ingredients'];
                    $aiProduct->setIngredients($ingredients);
                }
                
                // Extract upc
                if (isset($productData['upc']) && $productData['upc']) {
                    $aiProduct->setUpc($productData['upc']);
                }
                
                // Extract keywords (array to JSON string)
                if (isset($productData['keywords'])) {
                    $keywords = is_array($productData['keywords']) 
                        ? $this->jsonSerializer->serialize($productData['keywords'])
                        : $productData['keywords'];
                    $aiProduct->setKeywords($keywords);
                }
                
                // Extract pricing information
                if (isset($productData['pricing']) && is_array($productData['pricing'])) {
                    $pricing = $productData['pricing'];
                    
                    // USD pricing
                    if (isset($pricing['USD']) && is_array($pricing['USD'])) {
                        if (isset($pricing['USD']['min_price'])) {
                            $aiProduct->setPricingUsdMin((float)$pricing['USD']['min_price']);
                        }
                        if (isset($pricing['USD']['max_price'])) {
                            $aiProduct->setPricingUsdMax((float)$pricing['USD']['max_price']);
                        }
                    }
                    
                    // CAD pricing
                    if (isset($pricing['CAD']) && is_array($pricing['CAD'])) {
                        if (isset($pricing['CAD']['min_price'])) {
                            $aiProduct->setPricingCadMin((float)$pricing['CAD']['min_price']);
                        }
                        if (isset($pricing['CAD']['max_price'])) {
                            $aiProduct->setPricingCadMax((float)$pricing['CAD']['max_price']);
                        }
                    }
                }
                
                // Store everything else in additional_information as JSON
                $additionalData = [];
                foreach ($productData as $key => $value) {
                    // Skip the fields we're storing separately
                    if (!in_array($key, $fieldsToExtract) && 
                        !in_array($key, ['primary_keywords', 'secondary_keywords', 'item_index', 'generation_time', 'from_cache', 'error']) &&
                        $value !== null && $value !== '') {
                        $additionalData[$key] = $value;
                    }
                }
                
                // Store additional data as JSON
                if (!empty($additionalData)) {
                    $aiProduct->setAdditionalInformation($this->jsonSerializer->serialize($additionalData));
                }
                
                // Store full AI response as JSON (for reference)
                $aiProduct->setAiResponse($this->jsonSerializer->serialize($productData));
                
                // Store keywords if present
                if (isset($productData['primary_keywords'])) {
                    $primaryKeywords = is_array($productData['primary_keywords']) 
                        ? implode(',', $productData['primary_keywords'])
                        : $productData['primary_keywords'];
                    $aiProduct->setPrimaryKeywords($primaryKeywords);
                }
                
                if (isset($productData['secondary_keywords'])) {
                    $secondaryKeywords = is_array($productData['secondary_keywords']) 
                        ? implode(',', $productData['secondary_keywords'])
                        : $productData['secondary_keywords'];
                    $aiProduct->setSecondaryKeywords($secondaryKeywords);
                }
                
                // Magento product creation status (always start as not created)
                $aiProduct->setIsCreatedInMagento(false);
                $aiProduct->setMagentoProductId(null);
                
                // Save the AI product
                $this->aiProductRepository->save($aiProduct);
                $savedCount++;
                
                $this->logger->info('FileManager saveAiProductData: Saved AI product', [
                    'aiproduct_id' => $aiProduct->getAiproductId(),
                    'generatedcsv_id' => $aiProduct->getGeneratedcsvId(),
                    'product_name' => $aiProduct->getProductName()
                ]);
            }
            
            $this->logger->info('FileManager saveAiProductData: Successfully saved ' . $savedCount . ' out of ' . count($aiProductData) . ' products');
            
        } catch (\Exception $e) {
            $this->logger->error('Error saving AI product data: ' . $e->getMessage());
            throw new LocalizedException(__('Could not save AI product data: %1', $e->getMessage()));
        }
    }

    /**
     * Generate URL key from product name and SKU
     *
     * @param string $name
     * @param string $sku
     * @return string
     */
    private function generateUrlKey(string $name, string $sku): string
    {
        $urlKey = $name ?: $sku;
        $urlKey = strtolower($urlKey);
        $urlKey = preg_replace('/[^a-z0-9-_]/', '-', $urlKey);
        $urlKey = preg_replace('/-+/', '-', $urlKey);
        $urlKey = trim($urlKey, '-');
        
        return $urlKey;
    }

    /**
     * Extract custom attributes from AI product data
     *
     * @param array $productData
     * @return array
     */
    private function extractCustomAttributesFromAiData(array $productData): array
    {
        // Define standard product fields that should not be treated as custom attributes
        $standardFields = [
            'sku', 'name', 'description', 'short_description', 'price', 'special_price',
            'weight', 'qty', 'category', 'status', 'visibility', 'type', 'attribute_set',
            'tax_class', 'meta_title', 'meta_description', 'meta_keywords', 'url_key'
        ];
        
        $customAttributes = [];
        
        // Extract any additional fields as custom attributes
        foreach ($productData as $key => $value) {
            if (!in_array($key, $standardFields) && !empty($value)) {
                $customAttributes[$key] = $value;
            }
        }
        
        return $customAttributes;
    }
    
    /**
     * Create input reference CSV file for single product generation
     *
     * @param array $productData
     * @param string $fileName
     * @return string File path
     * @throws FileSystemException
     */
    public function createSingleProductInputFile(array $productData, string $fileName): string
    {
        $this->createDirectories(); // Ensure directories exist
        
        $filePath = self::INPUT_DIR . '/' . $fileName;
        
        $this->logger->info('SquadexaAI FileManager: Creating single product input file', [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'product_data' => $productData
        ]);
        
        // Prepare CSV data
        $csvData = [
            ['Product Name', 'Primary Keywords', 'Secondary Keywords', 'Include Pricing'],
            [
                $productData['product_name'],
                $productData['primary_keywords'],
                $productData['secondary_keywords'],
                $productData['include_pricing'] ? 'Yes' : 'No'
            ]
        ];
        
        // Write CSV file
        $this->varDirectory->writeFile($filePath, $this->arrayToCsv($csvData));
        
        $this->logger->info('SquadexaAI FileManager: Single product input file created', [
            'file_name' => $fileName,
            'file_path' => $filePath
        ]);
        
        return $filePath;
    }
    
    /**
     * Create response CSV file for single product generation
     *
     * @param array $apiResponse
     * @param string $fileName
     * @return string File path
     * @throws FileSystemException
     */
    /**
     * Create response CSV file for single product generation
     * Formats the API response according to the new CSV structure
     *
     * @param array $apiResponse
     * @param string $fileName
     * @return string File path
     * @throws LocalizedException
     */
    public function createSingleProductResponseFile(array $apiResponse, string $fileName): string
    {
        $this->createDirectories(); // Ensure directories exist
        
        $filePath = self::OUTPUT_DIR . '/' . $fileName;
        
        $this->logger->info('SquadexaAI FileManager: Creating single product response file', [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'response_keys' => array_keys($apiResponse)
        ]);
        
        // Prepare CSV header matching the new structure
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
        
        // Build CSV row
        $row = [];
        $row[] = $apiResponse['product_name'] ?? $apiResponse['name'] ?? '';
        $row[] = is_array($apiResponse['primary_keywords'] ?? null) 
            ? implode(',', $apiResponse['primary_keywords']) 
            : ($apiResponse['primary_keywords'] ?? '');
        $row[] = is_array($apiResponse['secondary_keywords'] ?? null) 
            ? implode(',', $apiResponse['secondary_keywords']) 
            : ($apiResponse['secondary_keywords'] ?? '');
        $row[] = isset($apiResponse['include_pricing']) ? ($apiResponse['include_pricing'] ? 'true' : 'false') : '';
        $row[] = $apiResponse['meta_title'] ?? '';
        $row[] = $apiResponse['meta_description'] ?? '';
        $row[] = $apiResponse['short_description'] ?? '';
        $row[] = $apiResponse['description'] ?? '';
        $row[] = is_array($apiResponse['key_features'] ?? null) 
            ? implode('|', $apiResponse['key_features']) 
            : ($apiResponse['key_features'] ?? '');
        $row[] = is_array($apiResponse['how_to_use'] ?? null) 
            ? implode('|', $apiResponse['how_to_use']) 
            : ($apiResponse['how_to_use'] ?? '');
        $row[] = is_array($apiResponse['ingredients'] ?? null) 
            ? implode('|', $apiResponse['ingredients']) 
            : ($apiResponse['ingredients'] ?? '');
        $row[] = $apiResponse['upc'] ?? '';
        $row[] = is_array($apiResponse['keywords'] ?? null) 
            ? implode(',', $apiResponse['keywords']) 
            : ($apiResponse['keywords'] ?? '');
        
        // Extract pricing
        $pricing = $apiResponse['pricing'] ?? [];
        $row[] = $pricing['USD']['min_price'] ?? '';
        $row[] = $pricing['USD']['max_price'] ?? '';
        $row[] = $pricing['CAD']['min_price'] ?? '';
        $row[] = $pricing['CAD']['max_price'] ?? '';
        
        // Write CSV file with headers and data
        $csvData = [$headers, $row];
        $this->varDirectory->writeFile($filePath, $this->arrayToCsv($csvData));
        
        $this->logger->info('SquadexaAI FileManager: Single product response file created', [
            'file_name' => $fileName,
            'file_path' => $filePath
        ]);
        
        return $filePath;
    }
    
    /**
     * Convert array to CSV string
     *
     * @param array $data
     * @return string
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Delete file from var directory
     *
     * @param string $fileName
     * @param string $type Type of file: 'input' or 'output'
     * @return bool True on success, false on failure
     */
    public function deleteFile(string $fileName, string $type = 'input'): bool
    {
        try {
            if (empty($fileName)) {
                $this->logger->warning('SquadexaAI FileManager: Attempted to delete file with empty filename');
                return false;
            }

            $this->createDirectories();
            $relativePath = ($type === 'input') ? self::INPUT_DIR . '/' . $fileName : self::OUTPUT_DIR . '/' . $fileName;
            
            if ($this->varDirectory->isExist($relativePath)) {
                $this->varDirectory->delete($relativePath);
                $this->logger->info('SquadexaAI FileManager: File deleted successfully', [
                    'file_name' => $fileName,
                    'type' => $type,
                    'relative_path' => $relativePath
                ]);
                return true;
            } else {
                $this->logger->warning('SquadexaAI FileManager: File does not exist', [
                    'file_name' => $fileName,
                    'type' => $type,
                    'relative_path' => $relativePath
                ]);
                return false;
            }
        } catch (FileSystemException $e) {
            $this->logger->error('SquadexaAI FileManager: Error deleting file', [
                'file_name' => $fileName,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI FileManager: Unexpected error deleting file', [
                'file_name' => $fileName,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Delete both input and output files for a GeneratedCsv record
     *
     * @param string|null $inputFileName
     * @param string|null $inputFilePath
     * @param string|null $responseFileName
     * @param string|null $responseFilePath
     * @return array Result with deletion status for each file
     */
    public function deleteGeneratedCsvFiles(
        ?string $inputFileName,
        ?string $inputFilePath,
        ?string $responseFileName,
        ?string $responseFilePath
    ): array {
        $result = [
            'input_deleted' => false,
            'output_deleted' => false,
            'errors' => []
        ];

        // Extract filename from path if full path is provided
        if (!empty($inputFilePath) && empty($inputFileName)) {
            $inputFileName = basename($inputFilePath);
        }
        if (!empty($responseFilePath) && empty($responseFileName)) {
            $responseFileName = basename($responseFilePath);
        }

        // Delete input file if exists
        if (!empty($inputFileName)) {
            $result['input_deleted'] = $this->deleteFile($inputFileName, 'input');
            if (!$result['input_deleted']) {
                $result['errors'][] = __('Failed to delete input file: %1', $inputFileName);
            }
        }

        // Delete output/response file if exists
        if (!empty($responseFileName)) {
            $result['output_deleted'] = $this->deleteFile($responseFileName, 'output');
            if (!$result['output_deleted']) {
                $result['errors'][] = __('Failed to delete response file: %1', $responseFileName);
            }
        }

        return $result;
    }
} 