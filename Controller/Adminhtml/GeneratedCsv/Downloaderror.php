<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem;

class Downloaderror extends Action
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
     * Downloaderror constructor.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
    }

    /**
     * Execute download error report
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('file');
        
        if (!$fileName) {
            $this->messageManager->addErrorMessage(__('No error report file specified.'));
            return $this->createRedirect();
        }

        // Validate filename to prevent directory traversal attacks
        if (strpos($fileName, '..') !== false || strpos($fileName, '/') !== false || strpos($fileName, '\\') !== false) {
            $this->messageManager->addErrorMessage(__('Invalid file name.'));
            return $this->createRedirect();
        }

        try {
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $filePath = 'AiBuilder/ErrorReports/' . $fileName;

            if (!$varDirectory->isExist($filePath)) {
                throw new LocalizedException(__('Error report file not found.'));
            }

            $fileContent = $varDirectory->readFile($filePath);
            
            return $this->fileFactory->create(
                $fileName,
                $fileContent,
                DirectoryList::VAR_DIR,
                'text/csv'
            );

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->createRedirect();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while downloading the error report.'));
            return $this->createRedirect();
        }
    }

    /**
     * Create redirect response
     *
     * @return Redirect
     */
    private function createRedirect(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index');
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_AIAutoProductBuilder::GeneratedCsv_download');
    }
} 