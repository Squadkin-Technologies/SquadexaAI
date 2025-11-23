<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model;

use Magento\Framework\Model\AbstractModel;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface;

class GeneratedCsv extends AbstractModel implements GeneratedCsvInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Squadkin\SquadexaAI\Model\ResourceModel\GeneratedCsv::class);
    }

    /**
     * @inheritDoc
     */
    public function getGeneratedcsvId()
    {
        return (int)$this->getData(self::GENERATEDCSV_ID);
    }

    /**
     * @inheritDoc
     */
    public function setGeneratedcsvId($generatedcsvId)
    {
        return $this->setData(self::GENERATEDCSV_ID, $generatedcsvId);
    }

    /**
     * @inheritDoc
     */
    public function getInputFileName()
    {
        return $this->getData(self::INPUT_FILE_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setInputFileName($inputFileName)
    {
        return $this->setData(self::INPUT_FILE_NAME, $inputFileName);
    }

    /**
     * @inheritDoc
     */
    public function getInputFilePath()
    {
        return $this->getData(self::INPUT_FILE_PATH);
    }

    /**
     * @inheritDoc
     */
    public function setInputFilePath($inputFilePath)
    {
        return $this->setData(self::INPUT_FILE_PATH, $inputFilePath);
    }

    /**
     * @inheritDoc
     */
    public function getResponseFileName()
    {
        return $this->getData(self::RESPONSE_FILE_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setResponseFileName($responseFileName)
    {
        return $this->setData(self::RESPONSE_FILE_NAME, $responseFileName);
    }

    /**
     * @inheritDoc
     */
    public function getResponseFilePath()
    {
        return $this->getData(self::RESPONSE_FILE_PATH);
    }

    /**
     * @inheritDoc
     */
    public function setResponseFilePath($responseFilePath)
    {
        return $this->setData(self::RESPONSE_FILE_PATH, $responseFilePath);
    }

    /**
     * @inheritDoc
     */
    public function getImportStatus()
    {
        return $this->getData(self::IMPORT_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setImportStatus($importStatus)
    {
        return $this->setData(self::IMPORT_STATUS, $importStatus);
    }

    /**
     * @inheritDoc
     */
    public function getImportedProductsCount()
    {
        return $this->getData(self::IMPORTED_PRODUCTS_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setImportedProductsCount($importedProductsCount)
    {
        return $this->setData(self::IMPORTED_PRODUCTS_COUNT, $importedProductsCount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalProductsCount()
    {
        return $this->getData(self::TOTAL_PRODUCTS_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalProductsCount($totalProductsCount)
    {
        return $this->setData(self::TOTAL_PRODUCTS_COUNT, $totalProductsCount);
    }

    /**
     * @inheritDoc
     */
    public function getImportErrorMessage()
    {
        return $this->getData(self::IMPORT_ERROR_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setImportErrorMessage($importErrorMessage)
    {
        return $this->setData(self::IMPORT_ERROR_MESSAGE, $importErrorMessage);
    }

    /**
     * @inheritDoc
     */
    public function getImportedAt()
    {
        return $this->getData(self::IMPORTED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setImportedAt($importedAt)
    {
        return $this->setData(self::IMPORTED_AT, $importedAt);
    }

    /**
     * @inheritDoc
     */
    public function getJobId()
    {
        return $this->getData(self::JOB_ID);
    }

    /**
     * @inheritDoc
     */
    public function setJobId($jobId)
    {
        return $this->setData(self::JOB_ID, $jobId);
    }
}
