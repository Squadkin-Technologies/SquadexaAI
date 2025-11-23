<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\GeneratedCsv;

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
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param RedirectFactory $resultRedirectFactory
     */
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

    /**
     * Execute action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $file = $this->getRequest()->getParam('file');
        if (!$file) {
            $this->messageManager->addErrorMessage(__('No error report file specified.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $fileName = basename($file); // phpcs:ignore
        $filePath = 'AIProductCreator/ErrorReports/' . $fileName;
        $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        if (!$varDirectory->isExist($filePath)) {
            $this->messageManager->addErrorMessage(__('Error report file does not exist.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
        $absolutePath = $varDirectory->getAbsolutePath($filePath);
        return $this->fileFactory->create(
            $fileName,
            [
                'type'  => 'filename',
                'value' => $absolutePath,
                'rm'    => false,
            ],
            DirectoryList::VAR_DIR
        );
    }

    /**
     * Check if action is allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv_view');
    }
}
