<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\Import\Source\CsvFactory;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\File\Csv;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Model\Config\Source\ImportStatus;
use Magento\Framework\Api\SearchCriteriaBuilder;

class MagentoImportService
{
    /**
     * @var ImportFactory
     */
    private $importFactory;

    /**
     * @var CsvFactory
     */
    private $csvSourceFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Csv
     */
    private $csvProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * MagentoImportService constructor.
     *
     * @param ImportFactory $importFactory
     * @param CsvFactory $csvSourceFactory
     * @param Filesystem $filesystem
     * @param Csv $csvProcessor
     * @param LoggerInterface $logger
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ImportFactory $importFactory,
        CsvFactory $csvSourceFactory,
        Filesystem $filesystem,
        Csv $csvProcessor,
        LoggerInterface $logger,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        AiProductRepositoryInterface $aiProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->importFactory = $importFactory;
        $this->csvSourceFactory = $csvSourceFactory;
        $this->filesystem = $filesystem;
        $this->csvProcessor = $csvProcessor;
        $this->logger = $logger;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->aiProductRepository = $aiProductRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Import products from generated CSV using Magento's import functionality
     *
     * @param GeneratedCsvInterface $generatedCsv
     * @param array $importOptions
     * @return array
     * @throws LocalizedException
     */
    public function importProductsFromGeneratedCsv(GeneratedCsvInterface $generatedCsv, array $importOptions = []): array
    {
        // Update status to processing
        $generatedCsv->setImportStatus(ImportStatus::STATUS_PROCESSING);
        $this->generatedCsvRepository->save($generatedCsv);

        try {
            $csvId = (int)$generatedCsv->getGeneratedcsvId();
            $this->logger->info(
                'SquadexaAI Import: Looking for AI products with CSV ID: ' . $csvId
            );
            
            // Get AI products for this CSV
            $aiProducts = $this->getAiProductsByCsvId($csvId);
          
            $this->logger->info('SquadexaAI Import: Found ' . count($aiProducts) . ' AI products');
            
            if (empty($aiProducts)) {
                $this->logger->error('SquadexaAI Import: No AI products found for CSV ID: ' . $csvId);
                
                // Let's check if there are ANY AI products in the database
                $allProducts = $this->aiProductRepository->getList($this->searchCriteriaBuilder->create());
                $this->logger->info(
                    'SquadexaAI Import: Total AI products in database: ' .
                    $allProducts->getTotalCount()
                );
                
                if ($allProducts->getTotalCount() > 0) {
                    foreach ($allProducts->getItems() as $product) {
                        $this->logger->info(
                            'SquadexaAI Import: Found AI product with CSV ID: ' .
                            $product->getGeneratedcsvId()
                        );
                    }
                }
                
                throw new LocalizedException(__('No AI products found for this CSV.'));
            }

            // Create Magento-compatible CSV for import
            $importCsvPath = $this->createImportCsv($aiProducts, (int)$generatedCsv->getGeneratedcsvId());
            
            // Perform the import using Magento's import functionality
            $importResult = $this->performMagentoImport($importCsvPath, $importOptions);
            
            // Update import status and counts
            if ($importResult['success']) {
                $generatedCsv->setImportStatus(ImportStatus::STATUS_COMPLETED);
                $generatedCsv->setImportedProductsCount($importResult['imported_count']);
                $generatedCsv->setTotalProductsCount($importResult['total_count']);
                $generatedCsv->setImportedAt(date('Y-m-d H:i:s'));
                $generatedCsv->setImportErrorMessage(null);
                
                // Update AI products as imported
                $this->updateAiProductsImportStatus($aiProducts, true);
            } else {
                $generatedCsv->setImportStatus(ImportStatus::STATUS_FAILED);
                $generatedCsv->setImportErrorMessage($importResult['error']);
                $generatedCsv->setImportedProductsCount($importResult['imported_count'] ?? 0);
                $generatedCsv->setTotalProductsCount($importResult['total_count'] ?? count($aiProducts));
            }
            
            $this->generatedCsvRepository->save($generatedCsv);
            
            // Clean up temporary import file
            $this->cleanupImportFile($importCsvPath);
            
            return $importResult;
            
        } catch (\Exception $e) {
            $this->logger->error('Import failed: ' . $e->getMessage());
            
            // Update status back to pending for retry
            $generatedCsv->setImportStatus(ImportStatus::STATUS_PENDING);
            $generatedCsv->setImportErrorMessage($e->getMessage());
            $this->generatedCsvRepository->save($generatedCsv);
            
            throw new LocalizedException(__('Import failed: %1', $e->getMessage()));
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
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('generatedcsv_id', $csvId)
            ->create();
            
        $searchResults = $this->aiProductRepository->getList($searchCriteria);
        return $searchResults->getItems();
    }

    /**
     * Create Magento-compatible CSV for import
     *
     * @param array $aiProducts
     * @param int $csvId
     * @return string
     * @throws LocalizedException
     */
    private function createImportCsv(array $aiProducts, int $csvId): string
    {
        $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $importDir = 'AIProductCreator/Import';
        
        if (!$varDirectory->isExist($importDir)) {
            $varDirectory->create($importDir);
        }
        
        $fileName = 'import_csv_' . $csvId . '_' . date('Y-m-d_H-i-s') . '.csv';
        $filePath = $importDir . '/' . $fileName;
        
        // Define Magento product import headers
        $headers = [
            'sku',
            'product_type',
            'attribute_set_code',
            'name',
            'description',
            'short_description',
            'weight',
            'product_online',
            'tax_class_name',
            'visibility',
            'price',
            'special_price',
            'qty',
            'out_of_stock_qty',
            'use_config_min_qty',
            'is_qty_decimal',
            'allow_backorders',
            'use_config_backorders',
            'min_cart_qty',
            'use_config_min_sale_qty',
            'max_cart_qty',
            'use_config_max_sale_qty',
            'is_in_stock',
            'notify_on_stock_below',
            'use_config_notify_stock_qty',
            'manage_stock',
            'use_config_manage_stock',
            'meta_title',
            'meta_keywords',
            'meta_description',
            'url_key',
            'created_at',
            'updated_at',
            'new_from_date',
            'new_to_date'
        ];
        
        $importData = [];
        $importData[] = $headers; // Add headers as first row
        
        foreach ($aiProducts as $aiProduct) {
            $importData[] = [
                $aiProduct->getSku(),
                $aiProduct->getType() ?: 'simple',
                $aiProduct->getAttributeSet() ?: 'Default',
                $aiProduct->getName(),
                $aiProduct->getDescription(),
                $aiProduct->getShortDescription(),
                $aiProduct->getWeight() ?: '',
                $aiProduct->getStatus() === 'Enabled' ? '1' : '0',
                $aiProduct->getTaxClass() ?: 'Taxable Goods',
                $this->convertVisibility($aiProduct->getVisibility()),
                $aiProduct->getPrice() ?: '',
                $aiProduct->getSpecialPrice() ?: '',
                $aiProduct->getQty() ?: '0',
                '0', // out_of_stock_qty
                '1', // use_config_min_qty
                '0', // is_qty_decimal
                '0', // allow_backorders
                '1', // use_config_backorders
                '1', // min_cart_qty
                '1', // use_config_min_sale_qty
                '0', // max_cart_qty
                '1', // use_config_max_sale_qty
                $aiProduct->getQty() > 0 ? '1' : '0', // is_in_stock
                '1', // notify_on_stock_below
                '1', // use_config_notify_stock_qty
                '1', // manage_stock
                '1', // use_config_manage_stock
                $aiProduct->getMetaTitle(),
                $aiProduct->getMetaKeywords(),
                $aiProduct->getMetaDescription(),
                $aiProduct->getUrlKey(),
                date('Y-m-d H:i:s'), // created_at
                date('Y-m-d H:i:s'), // updated_at
                '', // new_from_date
                ''  // new_to_date
            ];
        }
        
        // Write CSV file
        $stream = $varDirectory->openFile($filePath, 'w');
        foreach ($importData as $row) {
            $stream->writeCsv($row);
        }
        $stream->close();
        
        return $varDirectory->getAbsolutePath($filePath);
    }

    /**
     * Convert visibility text to Magento visibility ID
     *
     * @param string $visibility
     * @return string
     */
    private function convertVisibility(string $visibility): string
    {
        switch (strtolower($visibility)) {
            case 'not visible individually':
                return '1';
            case 'catalog':
                return '2';
            case 'search':
                return '3';
            case 'catalog, search':
            default:
                return '4';
        }
    }

    /**
     * Perform Magento import
     *
     * @param string $csvPath
     * @param array $importOptions
     * @return array
     * @throws LocalizedException
     */
    private function performMagentoImport(string $csvPath, array $importOptions): array
    {
        try {
            /** @var Import $import */
            $import = $this->importFactory->create();
            
            // Set import data
            $import->setData([
                Import::FIELD_NAME_IMG_FILE_DIR => '',
                Import::FIELD_NAME_VALIDATION_STRATEGY => $importOptions['validation_strategy'] ??
                    ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR,
                Import::FIELD_NAME_ALLOWED_ERROR_COUNT => $importOptions['allowed_error_count'] ?? 10,
                Import::FIELD_FIELD_SEPARATOR => $importOptions['field_separator'] ?? ',',
                Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR => $importOptions['multiple_value_separator'] ?? '|',
                'entity' => 'catalog_product',
                'behavior' => $importOptions['behavior'] ?? Import::BEHAVIOR_APPEND,
            ]);
            
            // Create source
            $source = $this->csvSourceFactory->create([
                'file' => $csvPath,
                'directory' => $this->filesystem->getDirectoryRead(DirectoryList::ROOT)
            ]);
            
            // Validate source
            $validationResult = $import->validateSource($source);
            
            if (!$validationResult) {
                $errorAggregator = $import->getErrorAggregator();
                $errors = [];
                foreach ($errorAggregator->getAllErrors() as $error) {
                    $errors[] = $error->getErrorMessage();
                }
                
                return [
                    'success' => false,
                    'error' => implode('; ', $errors),
                    'imported_count' => 0,
                    'total_count' => $import->getProcessedRowsCount()
                ];
            }
            
            // Import data
            $importResult = $import->importSource();
            
            if ($importResult) {
                return [
                    'success' => true,
                    'imported_count' => $import->getProcessedEntitiesCount(),
                    'total_count' => $import->getProcessedRowsCount(),
                    'errors' => []
                ];
            } else {
                $errorAggregator = $import->getErrorAggregator();
                $errors = [];
                foreach ($errorAggregator->getAllErrors() as $error) {
                    $errors[] = $error->getErrorMessage();
                }
                
                return [
                    'success' => false,
                    'error' => implode('; ', $errors),
                    'imported_count' => $import->getProcessedEntitiesCount(),
                    'total_count' => $import->getProcessedRowsCount()
                ];
            }
            
        } catch (\Exception $e) {
            throw new LocalizedException(__('Import execution failed: %1', $e->getMessage()));
        }
    }

    /**
     * Update AI products import status
     *
     * @param array $aiProducts
     * @param bool $imported
     */
    private function updateAiProductsImportStatus(array $aiProducts, bool $imported): void
    {
        foreach ($aiProducts as $aiProduct) {
            $aiProduct->setIsCreatedInMagento($imported);
            $this->aiProductRepository->save($aiProduct);
        }
    }

    /**
     * Clean up temporary import file
     *
     * @param string $filePath
     */
    private function cleanupImportFile(string $filePath): void
    {
        try {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (file_exists($filePath)) { // phpcs:ignore
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                unlink($filePath); // phpcs:ignore
            }
        } catch (\Exception $e) {
            $this->logger->warning('Unable to cleanup import file: ' . $e->getMessage());
        }
    }
}
