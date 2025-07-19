<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Api\Data;

interface GeneratedCsvInterface
{

    const RESPONSE_FILE_PATH = 'response_file_path';
    const INPUT_FILE_PATH = 'input_file_path';
    const GENERATEDCSV_ID = 'generatedcsv_id';
    const INPUT_FILE_NAME = 'input_file_name';
    const RESPONSE_FILE_NAME = 'response_file_name';

    /**
     * Get generatedcsv_id
     * @return string|null
     */
    public function getGeneratedcsvId();

    /**
     * Set generatedcsv_id
     * @param string $generatedcsvId
     * @return \Squadkin\AIAutoProductBuilder\GeneratedCsv\Api\Data\GeneratedCsvInterface
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
     * @return \Squadkin\AIAutoProductBuilder\GeneratedCsv\Api\Data\GeneratedCsvInterface
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
     * @return \Squadkin\AIAutoProductBuilder\GeneratedCsv\Api\Data\GeneratedCsvInterface
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
     * @return \Squadkin\AIAutoProductBuilder\GeneratedCsv\Api\Data\GeneratedCsvInterface
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
     * @return \Squadkin\AIAutoProductBuilder\GeneratedCsv\Api\Data\GeneratedCsvInterface
     */
    public function setResponseFilePath($responseFilePath);
}

