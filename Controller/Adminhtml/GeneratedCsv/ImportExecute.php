<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Service\MagentoImportService;
use Squadkin\SquadexaAI\Service\CsvValidationService;

class ImportExecute extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var MagentoImportService
     */
    private $importService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CsvValidationService
     */
    private $csvValidationService;

    /**
     * ImportExecute constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param MagentoImportService $importService
     * @param LoggerInterface $logger
     * @param CsvValidationService $csvValidationService
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        MagentoImportService $importService,
        LoggerInterface $logger,
        CsvValidationService $csvValidationService // phpcs:ignore
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->importService = $importService;
        $this->logger = $logger;
        $this->csvValidationService = $csvValidationService;
    }

    /**
     * Execute import
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $csvId = (int)$this->getRequest()->getParam('csv_id');
        
        $this->logger->info('SquadexaAI ImportExecute: Starting import execution for CSV ID: ' . $csvId);
        
        if (!$csvId) {
            $this->logger->error('SquadexaAI ImportExecute: Invalid CSV ID provided');
            return $resultJson->setData([
                'success' => false,
                'error' => __('Invalid CSV ID provided.')
            ]);
        }

        try {
            $this->logger->info('SquadexaAI ImportExecute: Loading CSV data for ID: ' . $csvId);
            $generatedCsv = $this->generatedCsvRepository->get($csvId);
            
            $importOptions = $this->getRequest()->getParams();
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $importOptionsStr = print_r($importOptions, true); // phpcs:ignore
            $this->logger->info('SquadexaAI ImportExecute: Import options: ' . $importOptionsStr);
            
            // --- CSV Validation Logic ---
            // @codingStandardsIgnoreLine
            $customCsvFile = $this->getRequest()->getFiles('custom_csv_file');
            $csvFilePath = '';
            $csvFileName = '';
            if ($customCsvFile && $customCsvFile['tmp_name']) {
                // Save custom CSV to var/AIProductCreator/CustomImports
                $customDir = 'AIProductCreator/CustomImports';
                $csvFileName = uniqid('custom_import_') . '.csv';
                $csvFilePath = $customDir . '/' . $csvFileName;
                $varDirectory = $this->csvValidationService->getFilesystem()
                    ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
                if (!$varDirectory->isExist($customDir)) {
                    $varDirectory->create($customDir);
                }
                // @codingStandardsIgnoreLine
                $tmpName = $customCsvFile['tmp_name'];
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $fileContent = file_get_contents($tmpName); // phpcs:ignore
                $varDirectory->writeFile($csvFilePath, $fileContent);
                $this->logger->info(
                    'SquadexaAI ImportExecute: Custom CSV uploaded and saved as ' . $csvFilePath
                );
            } else {
                // Use default linked CSV
                $csvFileName = $generatedCsv->getResponseFileName();
                $csvFilePath = 'AIProductCreator/Output/' . $csvFileName;
                $this->logger->info(
                    'SquadexaAI ImportExecute: Using default linked CSV: ' . $csvFilePath
                );
            }
            // Validate CSV
            $validationResult = $this->csvValidationService->validateCsvFile($csvFilePath);
            if (!$validationResult['is_valid']) {
                $this->logger->error('SquadexaAI ImportExecute: CSV validation failed');
                $errorReportFile = $this->csvValidationService->generateErrorReport(
                    $validationResult['error_aggregator'],
                    $csvFileName
                );
                $errorReportUrl = $this->getUrl(
                    'squadkin_squadexaai/generatedcsv/downloaderrorreport',
                    ['file' => $errorReportFile]
                );
                return $resultJson->setData([
                    'success' => false,
                    'error' => __('CSV validation failed. Please fix the errors and try again.'),
                    'validation_errors' => $validationResult['validation_messages'],
                    'error_report_url' => $errorReportUrl
                ]);
            }
            // --- End CSV Validation Logic ---

            // If custom CSV, import directly from file
            if ($customCsvFile && $customCsvFile['tmp_name']) {
                $this->logger->info(
                    'SquadexaAI ImportExecute: Importing directly from custom CSV file: ' . $csvFilePath
                );
                $importResult = $this->importService->performMagentoImport(
                    $csvFilePath,
                    $importOptions
                );
                return $resultJson->setData($importResult + ['custom_csv' => true]);
            }

            // Start import process (AI product flow)
            $this->logger->info(
                'SquadexaAI ImportExecute: Starting import service (AI product flow)'
            );
            $result = $this->importService->importProductsFromGeneratedCsv(
                $generatedCsv,
                $importOptions
            );
            $this->logger->info(
                'SquadexaAI ImportExecute: Import completed successfully. Imported: ' .
                $result['imported_count'] . ' / ' . $result['total_count']
            );
            return $resultJson->setData([
                'success' => true,
                'message' => __('Import completed successfully.'),
                'imported_count' => $result['imported_count'],
                'total_count' => $result['total_count'],
                'errors' => $result['errors'] ?? []
            ]);
            
        } catch (LocalizedException $e) {
            $this->logger->error(
                'SquadexaAI ImportExecute: LocalizedException: ' . $e->getMessage()
            );
            $this->logger->error(
                'SquadexaAI ImportExecute: Exception trace: ' . $e->getTraceAsString()
            );
            
            // Reset status to pending on error for retry
            try {
                $generatedCsv = $this->generatedCsvRepository->get($csvId);
                $generatedCsv->setImportStatus(\Squadkin\SquadexaAI\Model\Config\Source\ImportStatus::STATUS_PENDING);
                $generatedCsv->setImportErrorMessage($e->getMessage());
                $this->generatedCsvRepository->save($generatedCsv);
            } catch (\Exception $saveException) {
                $this->logger->error(
                    'SquadexaAI ImportExecute: Failed to reset status: ' . $saveException->getMessage()
                );
            }
            
            return $resultJson->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI ImportExecute: General Exception: ' . $e->getMessage());
            $this->logger->error('SquadexaAI ImportExecute: Exception trace: ' . $e->getTraceAsString());
            
            // Reset status to pending on error for retry
            try {
                $generatedCsv = $this->generatedCsvRepository->get($csvId);
                $generatedCsv->setImportStatus(\Squadkin\SquadexaAI\Model\Config\Source\ImportStatus::STATUS_PENDING);
                $generatedCsv->setImportErrorMessage($e->getMessage());
                $this->generatedCsvRepository->save($generatedCsv);
            } catch (\Exception $saveException) {
                $this->logger->error(
                    'SquadexaAI ImportExecute: Failed to reset status: ' . $saveException->getMessage()
                );
            }
            
            return $resultJson->setData([
                'success' => false,
                'error' => __('An unexpected error occurred during import: %1', $e->getMessage())
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
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv_save');
    }
}
