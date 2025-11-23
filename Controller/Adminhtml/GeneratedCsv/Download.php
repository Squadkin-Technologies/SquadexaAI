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
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Squadkin\SquadexaAI\Helper\FileManager;
use Psr\Log\LoggerInterface;

class Download extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Download constructor.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param FileManager $fileManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        FileManager $fileManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * Execute download action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('file');
        $type = $this->getRequest()->getParam('type'); // 'input' or 'output'

        $this->logger->info('SquadexaAI Download: Download request received', [
            'file_name' => $fileName,
            'type' => $type
        ]);

        if (!$fileName || !$type) {
            $this->logger->error('SquadexaAI Download: Invalid parameters');
            $this->messageManager->addErrorMessage(__('Invalid download parameters.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            // Get absolute file path for debugging
            $absolutePath = $this->fileManager->getAbsoluteFilePath($fileName, $type);
            $this->logger->info('SquadexaAI Download: Looking for file', [
                'absolute_path' => $absolutePath,
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                'file_exists' => file_exists($absolutePath) // phpcs:ignore
            ]);

            // Get file content
            $fileContent = $this->fileManager->getFileContent($fileName, $type);
            
            $this->logger->info('SquadexaAI Download: File content retrieved', [
                'content_length' => strlen($fileContent)
            ]);
            
            // Determine content type based on file extension
            $contentType = $this->getContentType($fileName);
            
            // Return file for download
            return $this->fileFactory->create(
                $fileName,
                $fileContent,
                \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
                $contentType
            );

        } catch (LocalizedException $e) {
            $this->logger->error('SquadexaAI Download: LocalizedException', [
                'error' => $e->getMessage(),
                'file_name' => $fileName,
                'type' => $type
            ]);
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Download: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_name' => $fileName,
                'type' => $type
            ]);
            $this->messageManager->addErrorMessage(
                __('An error occurred while downloading the file: %1', $e->getMessage())
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/index');
    }

    /**
     * Get content type based on file extension
     *
     * @param string $fileName
     * @return string
     */
    private function getContentType(string $fileName): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); // phpcs:ignore
        
        switch ($extension) {
            case 'csv':
                return 'text/csv';
            case 'xlsx':
                return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            case 'xls':
                return 'application/vnd.ms-excel';
            default:
                return 'application/octet-stream';
        }
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv_view');
    }
}
