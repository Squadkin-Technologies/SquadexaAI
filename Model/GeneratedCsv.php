<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Model;

use Magento\Framework\Model\AbstractModel;
use Squadkin\AIAutoProductBuilder\Api\Data\GeneratedCsvInterface;

class GeneratedCsv extends AbstractModel implements GeneratedCsvInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Squadkin\AIAutoProductBuilder\Model\ResourceModel\GeneratedCsv::class);
    }

    /**
     * @inheritDoc
     */
    public function getGeneratedcsvId()
    {
        return $this->getData(self::GENERATEDCSV_ID);
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
}

