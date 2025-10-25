<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api\Data;

interface GeneratedCsvInterface
{

    const RESPONSE_FILE_PATH = 'response_file_path';
    const INPUT_FILE_PATH = 'input_file_path';
    const GENERATEDCSV_ID = 'generatedcsv_id';
    const INPUT_FILE_NAME = 'input_file_name';
    const RESPONSE_FILE_NAME = 'response_file_name';
    const IMPORT_STATUS = 'import_status';
    const IMPORTED_PRODUCTS_COUNT = 'imported_products_count';
    const TOTAL_PRODUCTS_COUNT = 'total_products_count';
    const IMPORT_ERROR_MESSAGE = 'import_error_message';
    const IMPORTED_AT = 'imported_at';

    /**
     * Get generatedcsv_id
     * @return string|null
     */
    public function getGeneratedcsvId();

    /**
     * Set generatedcsv_id
     * @param string $generatedcsvId
     * @return \Squadkin\SquadexaAI\GeneratedCsv\Api\Data\GeneratedCsvInterface
     */
    public function setGeneratedcsvId($generatedcsvId);

    /**
     * Get input_file_name
     * @return string|null
     */
    public function getInputFileName();

    /**
     * Set input_file_name
     * @param string $inputFileName
     * @return \Squadkin\SquadexaAI\GeneratedCsv\Api\Data\GeneratedCsvInterface
     */
    public function setInputFileName($inputFileName);

    /**
     * Get input_file_path
     * @return string|null
     */
    public function getInputFilePath();

    /**
     * Set input_file_path
     * @param string $inputFilePath
     * @return \Squadkin\SquadexaAI\GeneratedCsv\Api\Data\GeneratedCsvInterface
     */
    public function setInputFilePath($inputFilePath);

    /**
     * Get response_file_name
     * @return string|null
     */
    public function getResponseFileName();

    /**
     * Set response_file_name
     * @param string $responseFileName
     * @return \Squadkin\SquadexaAI\GeneratedCsv\Api\Data\GeneratedCsvInterface
     */
    public function setResponseFileName($responseFileName);

    /**
     * Get response_file_path
     * @return string|null
     */
    public function getResponseFilePath();

    /**
     * Set response_file_path
     * @param string $responseFilePath
     * @return \Squadkin\SquadexaAI\GeneratedCsv\Api\Data\GeneratedCsvInterface
     */
    public function setResponseFilePath($responseFilePath);

    /**
     * Get import_status
     * @return string|null
     */
    public function getImportStatus();

    /**
     * Set import_status
     * @param string $importStatus
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface
     */
    public function setImportStatus($importStatus);

    /**
     * Get imported_products_count
     * @return int|null
     */
    public function getImportedProductsCount();

    /**
     * Set imported_products_count
     * @param int $importedProductsCount
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface
     */
    public function setImportedProductsCount($importedProductsCount);

    /**
     * Get total_products_count
     * @return int|null
     */
    public function getTotalProductsCount();

    /**
     * Set total_products_count
     * @param int $totalProductsCount
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface
     */
    public function setTotalProductsCount($totalProductsCount);

    /**
     * Get import_error_message
     * @return string|null
     */
    public function getImportErrorMessage();

    /**
     * Set import_error_message
     * @param string $importErrorMessage
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface
     */
    public function setImportErrorMessage($importErrorMessage);

    /**
     * Get imported_at
     * @return string|null
     */
    public function getImportedAt();

    /**
     * Set imported_at
     * @param string $importedAt
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface
     */
    public function setImportedAt($importedAt);
}

