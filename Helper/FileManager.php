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
    public const INPUT_DIR = 'AIProductCreator/Input';
    public const OUTPUT_DIR = 'AIProductCreator/Output';
    
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
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $fileContent = file_get_contents($fileData['tmp_name']); // phpcs:ignore
            
            // Save to var directory
            $this->varDirectory->writeFile($filePath, $fileContent);
            
            return $fileName;
        } catch (\Exception $e) {
            $this->logger->error('Error saving input file: ' . $e->getMessage());
            throw new LocalizedException(__('Could not save input file: %1', $e->getMessage()));
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
        $normalizedHeaders = array_map(function ($header) {
            return strtolower(trim($header));
        }, $headers);

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
                $errors[] = __(
                    'Required column "%1" is missing. Please ensure your CSV includes this column.',
                    $fieldLabel
                );
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
            // +2 because row 1 is header and array is 0-indexed
            $rowNumber = $rowIndex + 2; // phpcs:ignore
            
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
                if ($cleanKey === 'primarykeywords' ||
                    $cleanKey === 'primary_keywords' ||
                    $cleanKey === 'primary keywords') {
                    $primaryKeywordsValue = $value;
                }
                if ($cleanKey === 'secondarykeywords' ||
                    $cleanKey === 'secondary_keywords' ||
                    $cleanKey === 'secondary keywords') {
                    $secondaryKeywordsValue = $value;
                }
                if ($cleanKey === 'includepricing' ||
                    $cleanKey === 'include_pricing' ||
                    $cleanKey === 'include pricing') {
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
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $pathInfo = pathinfo($originalName); // phpcs:ignore
        $timestamp = date('Y-m-d_H-i-s');
        $randomString = substr(hash('sha256', uniqid((string)random_int(0, PHP_INT_MAX), true)), 0, 8);
        
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
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
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
        $allowedExtensions = ['csv'];
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION)); // phpcs:ignore
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new LocalizedException(__('Invalid file type. Only CSV and XLSX files are allowed.'));
        }
        
        return true;
    }

    /**
     * Save AI product data to database
     *
     * @param array $aiProductData
     * @param int|null $generatedCsvId Null for single product generation (no CSV file)
     * @param string $generationType
     * @return array Returns array with 'updated_count' and 'created_count' keys
     * @throws LocalizedException
     */
    public function saveAiProductData(array $aiProductData, ?int $generatedCsvId, string $generationType = 'csv'): array
    {
        $csvIdLog = $generatedCsvId !== null ? (string)$generatedCsvId : 'null (single product, no CSV)';
        $this->logger->info(
            'FileManager saveAiProductData: Starting to save ' . count($aiProductData) .
            ' products for CSV ID: ' . $csvIdLog . ', Type: ' . $generationType
        );
        
        try {
            $savedCount = 0;
            $updatedCount = 0;
            $createdCount = 0;
            $isUpdate = false;
            
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
                
                // Check if a product with the same name and generation type already exists
                // This applies to both single and CSV generation types
                // Note: We check by product_name and generation_type only, not by generatedcsv_id
                // This ensures that if the same product name appears in different CSV files,
                // it will update the existing product instead of creating a duplicate
                $aiProduct = null;
                $isUpdate = false;
                if ($productName) {
                    $existingCollection = $this->aiProductCollectionFactory->create();
                    $existingCollection->addFieldToFilter('product_name', $productName)
                        ->addFieldToFilter('generation_type', $generationType);
                    
                    // Do NOT filter by generatedcsv_id when checking for duplicates
                    // This allows updating existing products even if they came from a different CSV
                    
                    $existingCollection->setPageSize(1);
                    
                    $this->logger->info('FileManager saveAiProductData: Checking for existing product', [
                        'product_name' => $productName,
                        'generation_type' => $generationType,
                        'generatedcsv_id' => $generatedCsvId,
                        'collection_size' => $existingCollection->getSize()
                    ]);
                    
                    if ($existingCollection->getSize() > 0) {
                        $aiProduct = $existingCollection->getFirstItem();
                        $isUpdate = true;
                        $this->logger->info(
                            'FileManager saveAiProductData: Found existing product, ' .
                            'updating instead of creating new',
                            [
                                'aiproduct_id' => $aiProduct->getAiproductId(),
                                'product_name' => $productName,
                                'current_regeneration_count' => $aiProduct->getData('regeneration_count') ?? 0
                            ]
                        );
                    } else {
                        $this->logger->info(
                            'FileManager saveAiProductData: No existing product found, will create new one',
                            [
                                'product_name' => $productName
                            ]
                        );
                    }
                }
                
                // If no existing product found, create a new one
                if (!$aiProduct) {
                    $aiProduct = $this->aiProductFactory->create();
                }
                
                // Set required fields
                // For single product generation, generatedCsvId can be null
                // Explicitly set to null if not provided (important for database foreign key constraint)
                $aiProduct->setGeneratedcsvId($generatedCsvId);
                $aiProduct->setGenerationType($generationType);
                $aiProduct->setProductName($productName);
                
                // Increment regeneration_count if updating existing product
                if ($isUpdate) {
                    $currentCount = (int)($aiProduct->getData('regeneration_count') ?? 0);
                    $aiProduct->setData('regeneration_count', $currentCount + 1);
                    
                    // Explicitly set updated_at to current timestamp when updating
                    // Magento's ORM doesn't automatically update this field even with on_update="true"
                    $aiProduct->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
                    
                    $this->logger->info(
                        'FileManager saveAiProductData: Incrementing regeneration count and updating timestamp',
                        [
                            'old_count' => $currentCount,
                            'new_count' => $currentCount + 1
                        ]
                    );
                } else {
                    // New product, set regeneration_count to 0
                    $aiProduct->setData('regeneration_count', 0);
                }
                
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
                    $excludedKeys = [
                        'primary_keywords',
                        'secondary_keywords',
                        'item_index',
                        'generation_time',
                        'from_cache',
                        'error'
                    ];
                    if (!in_array($key, $fieldsToExtract) &&
                        !in_array($key, $excludedKeys) &&
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
                
                // When updating, preserve Magento product creation status and ID
                // Only reset if this is a new product
                if (!$isUpdate) {
                    // Magento product creation status (always start as not created for new products)
                    $aiProduct->setIsCreatedInMagento(false);
                    $aiProduct->setMagentoProductId(null);
                }
                // For updates, keep existing is_created_in_magento and magento_product_id values
                
                // Validate required fields before saving
                if (empty($aiProduct->getProductName())) {
                    $this->logger->error('FileManager saveAiProductData: Cannot save - product_name is empty', [
                        'product_data_keys' => array_keys($productData)
                    ]);
                    throw new LocalizedException(__('Cannot save AI product: product_name is required but was empty.'));
                }
                
                if (empty($aiProduct->getGenerationType())) {
                    $this->logger->error('FileManager saveAiProductData: Cannot save - generation_type is empty');
                    throw new LocalizedException(
                        __('Cannot save AI product: generation_type is required but was empty.')
                    );
                }
                
                // Save the AI product
                try {
                    $this->aiProductRepository->save($aiProduct);
                    $savedCount++;
                    
                    if ($isUpdate) {
                        $updatedCount++;
                    } else {
                        $createdCount++;
                    }
                    
                    // Log the raw data value to see if it's actually null
                    $rawGeneratedCsvId = $aiProduct->getData('generatedcsv_id');
                    $this->logger->info(
                        'FileManager saveAiProductData: Saved AI product',
                        [
                            'aiproduct_id' => $aiProduct->getAiproductId(),
                            'generatedcsv_id' => $aiProduct->getGeneratedcsvId(),
                            'generatedcsv_id_raw' => $rawGeneratedCsvId,
                            'generatedcsv_id_is_null' => ($rawGeneratedCsvId === null),
                            'product_name' => $aiProduct->getProductName(),
                            'generation_type' => $aiProduct->getGenerationType(),
                            'is_update' => $isUpdate,
                            'regeneration_count' => $aiProduct->getData('regeneration_count')
                        ]
                    );
                } catch (\Exception $saveException) {
                    $this->logger->error('FileManager saveAiProductData: Failed to save AI product', [
                        'error' => $saveException->getMessage(),
                        'trace' => $saveException->getTraceAsString(),
                        'product_name' => $aiProduct->getProductName(),
                        'generation_type' => $aiProduct->getGenerationType(),
                        'generatedcsv_id' => $aiProduct->getGeneratedcsvId()
                    ]);
                    throw new LocalizedException(
                        __('Failed to save AI product: %1', $saveException->getMessage()),
                        $saveException
                    );
                }
            }
            
            $this->logger->info(
                'FileManager saveAiProductData: Successfully saved ' . $savedCount .
                ' out of ' . count($aiProductData) . ' products',
                [
                'created' => $createdCount,
                'updated' => $updatedCount
                ]
            );
            
            return [
                'total_saved' => $savedCount,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error saving AI product data: ' . $e->getMessage());
            throw new LocalizedException(__('Could not save AI product data: %1', $e->getMessage()));
        }
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
            $relativePath = ($type === 'input')
                ? self::INPUT_DIR . '/' . $fileName
                : self::OUTPUT_DIR . '/' . $fileName;
            
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
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $inputFileName = basename($inputFilePath); // phpcs:ignore
        }
        if (!empty($responseFilePath) && empty($responseFileName)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $responseFileName = basename($responseFilePath); // phpcs:ignore
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
