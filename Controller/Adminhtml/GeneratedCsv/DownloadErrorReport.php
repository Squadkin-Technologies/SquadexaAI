<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RedirectFactory;

class DownloadErrorReport extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    public function execute()
    {
        $file = $this->getRequest()->getParam('file');
        if (!$file) {
            $this->messageManager->addErrorMessage(__('No error report file specified.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
        $filePath = 'AiBuilder/ErrorReports/' . basename($file);
        $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        if (!$varDirectory->isExist($filePath)) {
            $this->messageManager->addErrorMessage(__('Error report file does not exist.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
        $absolutePath = $varDirectory->getAbsolutePath($filePath);
        return $this->fileFactory->create(
            basename($file),
            [
                'type'  => 'filename',
                'value' => $absolutePath,
                'rm'    => false,
            ],
            DirectoryList::VAR_DIR
        );
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Squadkin_AIAutoProductBuilder::GeneratedCsv_view');
    }
} 