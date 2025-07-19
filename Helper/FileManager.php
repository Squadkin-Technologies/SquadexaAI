<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Helper;

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
use Squadkin\AIAutoProductBuilder\Api\AiProductRepositoryInterface;
use Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterfaceFactory;

class FileManager extends AbstractHelper
{
    const INPUT_DIR = 'AiBuilder/Input';
    const OUTPUT_DIR = 'AiBuilder/Output';
    
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
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        Csv $csvProcessor,
        Curl $curl,
        Json $jsonSerializer,
        LoggerInterface $logger,
        AiProductRepositoryInterface $aiProductRepository,
        AiProductInterfaceFactory $aiProductFactory
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
     * @return array
     * @throws LocalizedException
     */
    public function processWithAI(string $inputFileName): array
    {
        try {
            $inputFilePath = self::INPUT_DIR . '/' . $inputFileName;
            $fullPath = $this->varDirectory->getAbsolutePath($inputFilePath);
            
            // Read file content
            $fileContent = $this->varDirectory->readFile($inputFilePath);
            
            // For now, using a mock AI API endpoint
            // This will be replaced with the actual API later
            $apiResponse = $this->callMockAIAPI($fileContent, $inputFileName);
            
            return $apiResponse;
        } catch (\Exception $e) {
            $this->logger->error('Error processing file with AI: ' . $e->getMessage());
            throw new LocalizedException(__('Could not process file with AI: %1', $e->getMessage()));
        }
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
            
            // Convert array data to CSV format
            $csvContent = $this->convertArrayToCsv($data);
            
            // Save to var directory
            $this->varDirectory->writeFile($outputFilePath, $csvContent);
            
            return $outputFileName;
        } catch (\Exception $e) {
            $this->logger->error('Error saving output file: ' . $e->getMessage());
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
            
            if (!$this->varDirectory->isExist($filePath)) {
                throw new LocalizedException(__('File not found: %1', $fileName));
            }
            
            return $this->varDirectory->readFile($filePath);
        } catch (\Exception $e) {
            $this->logger->error('Error reading file: ' . $e->getMessage());
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
        
        return $pathInfo['filename'] . '_' . $timestamp . '_' . $randomString . '.' . $pathInfo['extension'];
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
        return 'ai_generated_' . $pathInfo['filename'] . '.csv';
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
        
        // Add header row if data is associative array
        if (is_array($data[0])) {
            fputcsv($output, array_keys($data[0]), ',', '"', '\\');
        }
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, is_array($row) ? $row : [$row], ',', '"', '\\');
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }

    /**
     * Mock AI API call (to be replaced with actual API)
     *
     * @param string $fileContent
     * @param string $fileName
     * @return array
     */
    private function callMockAIAPI(string $fileContent, string $fileName): array
    {
        // Mock AI API response - generates sample product data
        // This simulates what the real AI API would return
        
        $mockProducts = [
            [
                'sku' => 'PROD-001',
                'name' => 'AI Generated Product 1',
                'price' => '29.99',
                'description' => 'This is a product generated by AI based on your input file.',
                'category' => 'Electronics',
                'status' => 'Enabled',
                'visibility' => 'Catalog, Search',
                'weight' => '1.5',
                'qty' => '100'
            ],
            [
                'sku' => 'PROD-002',
                'name' => 'AI Generated Product 2',
                'price' => '49.99',
                'description' => 'Another AI generated product with enhanced features.',
                'category' => 'Home & Garden',
                'status' => 'Enabled',
                'visibility' => 'Catalog, Search',
                'weight' => '2.0',
                'qty' => '50'
            ],
            [
                'sku' => 'PROD-003',
                'name' => 'AI Generated Product 3',
                'price' => '19.99',
                'description' => 'Budget-friendly AI generated product.',
                'category' => 'Fashion',
                'status' => 'Enabled',
                'visibility' => 'Catalog, Search',
                'weight' => '0.5',
                'qty' => '200'
            ]
        ];
        
        // Log the mock API call
        $this->logger->info('Mock AI API called for file: ' . $fileName);
        
        return $mockProducts;
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
    public function saveAiProductData(array $aiProductData, int $generatedCsvId): void
    {
        try {
            foreach ($aiProductData as $productData) {
                $aiProduct = $this->aiProductFactory->create();
                
                // Set basic product data
                $aiProduct->setGeneratedcsvId($generatedCsvId);
                $aiProduct->setSku($productData['sku'] ?? '');
                $aiProduct->setName($productData['name'] ?? '');
                $aiProduct->setDescription($productData['description'] ?? '');
                $aiProduct->setShortDescription($productData['short_description'] ?? $productData['description'] ?? '');
                $aiProduct->setPrice((float)($productData['price'] ?? 0));
                $aiProduct->setSpecialPrice(!empty($productData['special_price']) ? (float)$productData['special_price'] : null);
                $aiProduct->setWeight((float)($productData['weight'] ?? 0));
                $aiProduct->setQty((int)($productData['qty'] ?? 0));
                $aiProduct->setCategory($productData['category'] ?? '');
                $aiProduct->setStatus($productData['status'] ?? 'Enabled');
                $aiProduct->setVisibility($productData['visibility'] ?? 'Catalog, Search');
                $aiProduct->setType($productData['type'] ?? 'simple');
                $aiProduct->setAttributeSet($productData['attribute_set'] ?? 'Default');
                $aiProduct->setTaxClass($productData['tax_class'] ?? '');
                
                // Set SEO data
                $aiProduct->setMetaTitle($productData['meta_title'] ?? $productData['name'] ?? '');
                $aiProduct->setMetaDescription($productData['meta_description'] ?? $productData['description'] ?? '');
                $aiProduct->setMetaKeywords($productData['meta_keywords'] ?? '');
                $aiProduct->setUrlKey($this->generateUrlKey($productData['name'] ?? '', $productData['sku'] ?? ''));
                
                // Set Magento product creation status
                $aiProduct->setIsCreatedInMagento(false);
                $aiProduct->setMagentoProductId(null);
                
                // Process custom attributes from AI data
                $customAttributes = $this->extractCustomAttributesFromAiData($productData);
                if (!empty($customAttributes)) {
                    $aiProduct->setCustomAttributes($customAttributes);
                }
                
                // Save the AI product
                $this->aiProductRepository->save($aiProduct);
            }
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
} 