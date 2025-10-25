<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;

class ImportErrors extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * ImportErrors constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
    }

    /**
     * Execute import errors display
     *
     * @return Page|Json
     */
    public function execute()
    {
        $csvId = (int)$this->getRequest()->getParam('id');
        
        if (!$csvId) {
            if ($this->getRequest()->isAjax()) {
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData([
                    'error' => true,
                    'message' => __('Invalid CSV ID provided.')
                ]);
            }
            
            $this->messageManager->addErrorMessage(__('Invalid CSV ID provided.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('squadkin_squadexaai/generatedcsv/index');
        }

        try {
            $generatedCsv = $this->generatedCsvRepository->get($csvId);
            
            if ($this->getRequest()->isAjax()) {
                // Return JSON for AJAX requests
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData([
                    'error' => false,
                    'error_message' => $generatedCsv->getImportErrorMessage(),
                    'import_status' => $generatedCsv->getImportStatus(),
                    'imported_count' => $generatedCsv->getImportedProductsCount(),
                    'total_count' => $generatedCsv->getTotalProductsCount(),
                    'import_log' => $this->parseImportLog($generatedCsv->getImportErrorMessage()),
                    'failed_products' => [] // Could be expanded to show specific product failures
                ]);
            } else {
                // Return HTML page for direct access
                $resultPage = $this->resultPageFactory->create();
                $resultPage->setActiveMenu('Squadkin_SquadexaAI::squadkin_squadexaai_generatedcsv');
                $resultPage->getConfig()->getTitle()->prepend(__('Import Error Details'));
                
                // Pass data to the block
                $block = $resultPage->getLayout()->getBlock('import.errors.content');
                if ($block) {
                    $block->setGeneratedCsv($generatedCsv);
                }
                
                return $resultPage;
            }
            
        } catch (\Exception $e) {
            if ($this->getRequest()->isAjax()) {
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData([
                    'error' => true,
                    'message' => __('Unable to load error details: %1', $e->getMessage())
                ]);
            }
            
            $this->messageManager->addErrorMessage(__('Unable to load error details: %1', $e->getMessage()));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('squadkin_squadexaai/generatedcsv/index');
        }
    }

    /**
     * Parse import log from error message
     *
     * @param string|null $errorMessage
     * @return array
     */
    private function parseImportLog(?string $errorMessage): array
    {
        if (!$errorMessage) {
            return [];
        }

        // Simple log parsing - split by common delimiters
        $logEntries = [];
        $lines = explode('\n', $errorMessage);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $logEntries[] = $line;
            }
        }
        
        // If no line breaks, try semicolons
        if (count($logEntries) <= 1 && strpos($errorMessage, ';') !== false) {
            $logEntries = array_filter(explode(';', $errorMessage), function($entry) {
                return !empty(trim($entry));
            });
        }
        
        return array_map('trim', $logEntries);
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv_save');
    }
} 