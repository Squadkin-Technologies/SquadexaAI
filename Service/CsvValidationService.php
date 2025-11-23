<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterfaceFactory;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Psr\Log\LoggerInterface;

class CsvValidationService
{
    /**
     * Error message templates following Magento standards
     */
    public const ERROR_NAME_IS_REQUIRED = 'Name field is required for AI generation';
    public const ERROR_INVALID_FILE_FORMAT = 'Invalid file format';
    public const ERROR_EMPTY_FILE = 'File is empty or has no data rows';
    public const ERROR_INVALID_HEADER = 'Invalid CSV header structure';
    public const ERROR_MISSING_REQUIRED_COLUMNS = 'Missing required columns: %s';
    public const ERROR_DUPLICATE_HEADERS = 'Duplicate column headers found: %s';
    public const ERROR_INVALID_ROW_DATA = 'Invalid data in row %s: %s';
    /**
     * Error message for missing name column
     */
    public const ERROR_MISSING_NAME_COLUMN = 'Name column is missing or empty in row %s';
    
    /**
     * Error message for invalid data type
     */
    public const ERROR_INVALID_DATA_TYPE = 'Invalid data type in column "%s", row %s';

    /**
     * Required columns for AI generation
     */
    public const REQUIRED_COLUMNS = ['name'];

    /**
     * Optional columns that are commonly used
     */
    public const OPTIONAL_COLUMNS = [
        'sku', 'price', 'qty', 'weight', 'category', 'status', 'visibility',
        'description', 'short_description', 'meta_title', 'meta_description',
        'meta_keywords', 'attribute_set', 'type', 'tax_class'
    ];

    /**
     * @var Csv
     */
    private $csvProcessor;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var WriteInterface
     */
    private $varDirectory;

    /**
     * @var ProcessingErrorAggregatorInterfaceFactory
     */
    private $errorAggregatorFactory;

    /**
     * @var LoggerInterface
     */
    private $logger; // phpcs:ignore

    /**
     * @param Csv $csvProcessor
     * @param Filesystem $filesystem
     * @param ProcessingErrorAggregatorInterfaceFactory $errorAggregatorFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Csv $csvProcessor,
        Filesystem $filesystem,
        ProcessingErrorAggregatorInterfaceFactory $errorAggregatorFactory,
        LoggerInterface $logger
    ) {
        $this->csvProcessor = $csvProcessor;
        $this->filesystem = $filesystem;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->errorAggregatorFactory = $errorAggregatorFactory;
        $this->logger = $logger;
    }

    /**
     * Validate CSV file following Magento standards
     *
     * @param string $filePath
     * @return array
     * @throws LocalizedException
     */
    public function validateCsvFile(string $filePath): array
    {
        $errorAggregator = $this->errorAggregatorFactory->create();
        $validationResult = [
            'is_valid' => false,
            'error_aggregator' => $errorAggregator,
            'processed_rows' => 0,
            'valid_rows' => 0,
            'headers' => [],
            'sample_data' => [],
            'validation_messages' => []
        ];

        try {
            // Check if file exists
            if (!$this->varDirectory->isExist($filePath)) {
                $this->addError($errorAggregator, self::ERROR_INVALID_FILE_FORMAT, 0, null, 'File not found');
                return $validationResult;
            }

            // Read CSV file
            $fullPath = $this->varDirectory->getAbsolutePath($filePath);
            $csvData = $this->csvProcessor->getData($fullPath);

            if (empty($csvData)) {
                $this->addError($errorAggregator, self::ERROR_EMPTY_FILE, 0, null, 'CSV file is empty');
                return $validationResult;
            }

            // Extract headers
            $headers = array_shift($csvData);
            $validationResult['headers'] = $headers;

            // Validate headers
            $headerValidation = $this->validateHeaders($headers, $errorAggregator);
            if (!$headerValidation) {
                return $validationResult;
            }

            // Validate data rows
            $rowValidation = $this->validateDataRows($csvData, $headers, $errorAggregator);
            $validationResult['processed_rows'] = count($csvData);
            $validationResult['valid_rows'] = $rowValidation['valid_rows'];
            $validationResult['sample_data'] = $rowValidation['sample_data'];

            // Determine overall validation result
            $validationResult['is_valid'] = !$errorAggregator->hasToBeTerminated() &&
                                          $errorAggregator->getErrorsCount() === 0;

            // Generate validation messages
            $validationResult['validation_messages'] = $this->generateValidationMessages(
                $errorAggregator,
                $validationResult
            );

        } catch (\Exception $e) {
            $this->logger->error('CSV validation error: ' . $e->getMessage());
            $this->addError($errorAggregator, self::ERROR_INVALID_FILE_FORMAT, 0, null, $e->getMessage());
        }

        return $validationResult;
    }

    /**
     * Validate CSV headers
     *
     * @param array $headers
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return bool
     */
    private function validateHeaders(array $headers, ProcessingErrorAggregatorInterface $errorAggregator): bool
    {
        if (empty($headers)) {
            $this->addError($errorAggregator, self::ERROR_INVALID_HEADER, 0, null, 'No headers found');
            return false;
        }

        // Normalize headers (trim whitespace, convert to lowercase for comparison)
        $normalizedHeaders = array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);

        // Check for duplicate headers
        $duplicates = array_diff_key($normalizedHeaders, array_unique($normalizedHeaders));
        if (!empty($duplicates)) {
            $this->addError(
                $errorAggregator,
                self::ERROR_DUPLICATE_HEADERS,
                0,
                null,
                implode(', ', array_unique($duplicates))
            );
            return false;
        }

        // Check for required columns
        $missingRequired = [];
        foreach (self::REQUIRED_COLUMNS as $required) {
            if (!in_array($required, $normalizedHeaders)) {
                $missingRequired[] = $required;
            }
        }

        if (!empty($missingRequired)) {
            $this->addError(
                $errorAggregator,
                self::ERROR_MISSING_REQUIRED_COLUMNS,
                0,
                null,
                implode(', ', $missingRequired)
            );
            return false;
        }

        return true;
    }

    /**
     * Validate data rows
     *
     * @param array $dataRows
     * @param array $headers
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return array
     */
    private function validateDataRows(
        array $dataRows,
        array $headers,
        ProcessingErrorAggregatorInterface $errorAggregator
    ): array {
        $validRows = 0;
        $sampleData = [];
        $headerCount = count($headers);
        
        // Normalize headers for comparison
        $normalizedHeaders = array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);
        
        $nameColumnIndex = array_search('name', $normalizedHeaders);

        foreach ($dataRows as $rowIndex => $row) {
            $actualRowNumber = $rowIndex + 2; // +2 because we removed header and array is 0-indexed
            $isRowValid = true;

            // Check if row has correct number of columns
            if (count($row) !== $headerCount) {
                $this->addError(
                    $errorAggregator,
                    self::ERROR_INVALID_ROW_DATA,
                    $actualRowNumber,
                    null,
                    'Column count mismatch'
                );
                $isRowValid = false;
                continue;
            }

            // Check if Name field is present and not empty (required for AI generation)
            if ($nameColumnIndex !== false) {
                $nameValue = isset($row[$nameColumnIndex]) ? trim($row[$nameColumnIndex]) : '';
                if (empty($nameValue)) {
                    $this->addError(
                        $errorAggregator,
                        self::ERROR_MISSING_NAME_COLUMN,
                        $actualRowNumber,
                        'name',
                        'Name is required for AI generation'
                    );
                    $isRowValid = false;
                }
            }

            // Perform basic data type validation
            $this->validateRowDataTypes(
                $row,
                $headers,
                $normalizedHeaders,
                $actualRowNumber,
                $errorAggregator,
                $isRowValid
            );

            if ($isRowValid) {
                $validRows++;
                
                // Collect sample data (first 5 valid rows)
                if (count($sampleData) < 5) {
                    $rowData = array_combine($headers, $row);
                    $sampleData[] = $rowData;
                }
            }
        }

        return [
            'valid_rows' => $validRows,
            'sample_data' => $sampleData
        ];
    }

    /**
     * Validate data types in a row
     *
     * @param array $row
     * @param array $headers
     * @param array $normalizedHeaders
     * @param int $rowNumber
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param bool &$isRowValid
     */
    private function validateRowDataTypes(
        array                                 $row,
        array                                 $headers,
        array                                 $normalizedHeaders,
        int                                   $rowNumber,
        ProcessingErrorAggregatorInterface    $errorAggregator,
        bool                                  &$isRowValid
    ): void {
        // Define expected data types for certain columns
        $numericColumns = ['price', 'special_price', 'weight', 'qty'];
        $booleanColumns = ['status']; // will be validated as enabled/disabled

        foreach ($row as $columnIndex => $value) {
            if (!isset($normalizedHeaders[$columnIndex])) {
                continue;
            }

            $columnName = $normalizedHeaders[$columnIndex];
            $value = trim($value);

            // Skip empty values (they might be optional)
            if ($value === '') {
                continue;
            }

            // Validate numeric columns
            if (in_array($columnName, $numericColumns) && !is_numeric($value)) {
                $this->addError(
                    $errorAggregator,
                    self::ERROR_INVALID_DATA_TYPE,
                    $rowNumber,
                    $columnName,
                    'Expected numeric value, got: ' . $value
                );
                $isRowValid = false;
            }

            // Validate boolean-like columns
            if (in_array($columnName, $booleanColumns)) {
                $validBooleanValues = ['enabled', 'disabled', '1', '0', 'yes', 'no', 'true', 'false'];
                if (!in_array(strtolower($value), $validBooleanValues)) {
                    $this->addError(
                        $errorAggregator,
                        self::ERROR_INVALID_DATA_TYPE,
                        $rowNumber,
                        $columnName,
                        'Expected boolean-like value (enabled/disabled, 1/0, yes/no, true/false), got: ' . $value
                    );
                    $isRowValid = false;
                }
            }
        }
    }

    /**
     * Add error to error aggregator
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param string $errorCode
     * @param int $rowNumber
     * @param string|null $columnName
     * @param string $errorMessage
     */
    private function addError(
        ProcessingErrorAggregatorInterface $errorAggregator,
        string $errorCode,
        int $rowNumber,
        ?string $columnName,
        string $errorMessage
    ): void {
        $errorAggregator->addError(
            $errorCode,
            ProcessingError::ERROR_LEVEL_CRITICAL,
            $rowNumber,
            $columnName,
            $errorMessage
        );
    }

    /**
     * Generate validation messages
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param array $validationResult
     * @return array
     */
    private function generateValidationMessages(
        ProcessingErrorAggregatorInterface $errorAggregator,
        array $validationResult
    ): array {
        $messages = [];

        if ($validationResult['is_valid']) {
            $messages[] = __('File is valid and ready for AI processing.');
            $messages[] = __(
                'Processed %1 rows, %2 valid rows found.',
                $validationResult['processed_rows'],
                $validationResult['valid_rows']
            );
        } else {
            $messages[] = __('File validation failed. Please fix the errors below:');
            
            // Add error details
            $errors = $errorAggregator->getAllErrors();
            foreach ($errors as $error) {
                $messages[] = $error->getErrorMessage();
            }
        }

        return $messages;
    }

    /**
     * Generate error report for download
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param string $originalFileName
     * @return string Path to error report file
     * @throws LocalizedException
     */
    public function generateErrorReport(
        ProcessingErrorAggregatorInterface $errorAggregator,
        string $originalFileName
    ): string {
        if (!$errorAggregator->getErrorsCount()) {
            throw new LocalizedException(__('No errors to report'));
        }

        $errorReportData = [];
        $errorReportData[] = ['Row', 'Column', 'Error Code', 'Error Message']; // Header

        $errors = $errorAggregator->getAllErrors();
        foreach ($errors as $error) {
            $errorReportData[] = [
                $error->getRowNumber() ?: 'N/A',
                $error->getColumnName() ?: 'N/A',
                $error->getErrorCode(),
                $error->getErrorMessage()
            ];
        }

        // Generate error report file name
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $pathInfo = pathinfo($originalFileName); // phpcs:ignore
        $errorFileName = 'error_report_' . $pathInfo['filename'] . '_' . date('Y-m-d_H-i-s') . '.csv';
        $errorFilePath = 'AIProductCreator/ErrorReports/' . $errorFileName;

        // Create error reports directory
        $errorReportsDir = 'AIProductCreator/ErrorReports';
        if (!$this->varDirectory->isExist($errorReportsDir)) {
            $this->varDirectory->create($errorReportsDir);
        }

        // Convert error data to CSV
        $csvContent = $this->convertArrayToCsv($errorReportData);
        $this->varDirectory->writeFile($errorFilePath, $csvContent);

        return $errorFileName;
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

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $output = fopen('php://temp', 'w'); // phpcs:ignore

        foreach ($data as $row) {
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

    /**
     * Get supported file extensions
     *
     * @return array
     */
    public function getSupportedExtensions(): array
    {
        return ['csv'];
    }

    /**
     * Check if Name column exists and has data
     *
     * @param array $headers
     * @param array $sampleData
     * @return bool
     */
    public function hasValidNameData(array $headers, array $sampleData): bool
    {
        $normalizedHeaders = array_map('strtolower', array_map('trim', $headers));
        
        if (!in_array('name', $normalizedHeaders)) {
            return false;
        }

        // Check if at least some rows have name data
        foreach ($sampleData as $row) {
            if (!empty($row['name']) || !empty($row['Name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Filesystem instance
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
}
