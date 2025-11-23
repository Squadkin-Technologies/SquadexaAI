<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\GeneratedCsv;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;

class Upload extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Squadkin_SquadexaAI::generatedcsv/upload.phtml';

    /**
     * Upload constructor.
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
     * Get upload form action URL
     *
     * @return string
     */
    public function getUploadUrl(): string
    {
        return $this->getUrl('squadkin_squadexaai/generatedcsv/upload');
    }

    /**
     * Get form key for CSRF protection
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get accepted file types
     *
     * @return string
     */
    public function getAcceptedFileTypes(): string
    {
        return '.csv,.xlsx';
    }

    /**
     * Get maximum file size in MB
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return 10; // 10MB
    }
}
