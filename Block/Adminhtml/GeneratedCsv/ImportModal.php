<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\GeneratedCsv;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface;

class ImportModal extends Template
{
    /**
     * @var GeneratedCsvInterface
     */
    private $generatedCsv;

    /**
     * ImportModal constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Set generated CSV data
     *
     * @param GeneratedCsvInterface $generatedCsv
     */
    public function setGeneratedCsv(GeneratedCsvInterface $generatedCsv): void
    {
        $this->generatedCsv = $generatedCsv;
    }

    /**
     * Get generated CSV
     *
     * @return GeneratedCsvInterface|null
     */
    public function getGeneratedCsv(): ?GeneratedCsvInterface
    {
        return $this->generatedCsv;
    }

    /**
     * Get import execute URL
     *
     * @return string
     */
    public function getImportExecuteUrl(): string
    {
        return $this->getUrl('squadkin_squadexaai/generatedcsv/importexecute');
    }

    /**
     * Get validation strategies
     *
     * @return array
     */
    public function getValidationStrategies(): array
    {
        return [
            ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR => __('Stop on Error'),
            ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS => __('Skip error entries')
        ];
    }

    /**
     * Get import behaviors
     *
     * @return array
     */
    public function getImportBehaviors(): array
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add/Update Complex Data'),
            Import::BEHAVIOR_ADD_UPDATE => __('Add/Update'),
            Import::BEHAVIOR_REPLACE => __('Replace'),
            Import::BEHAVIOR_DELETE => __('Delete')
        ];
    }

    /**
     * Get field separators
     *
     * @return array
     */
    public function getFieldSeparators(): array
    {
        return [
            ',' => __('Comma (,)'),
            ';' => __('Semicolon (;)'),
            '\t' => __('Tab'),
            '|' => __('Pipe (|)')
        ];
    }

    /**
     * Get multiple value separators
     *
     * @return array
     */
    public function getMultipleValueSeparators(): array
    {
        return [
            '|' => __('Pipe (|)'),
            ',' => __('Comma (,)'),
            ';' => __('Semicolon (;)')
        ];
    }

    /**
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('squadkin_squadexaai/generatedcsv/index');
    }
} 