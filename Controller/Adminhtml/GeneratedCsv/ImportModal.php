<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;

class ImportModal extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ImportModal constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->logger = $logger;
    }

    /**
     * Execute import modal
     *
     * @return Page
     */
    public function execute(): Page
    {
        $csvId = (int)$this->getRequest()->getParam('id');
        
        $this->logger->info('SquadexaAI ImportModal: Starting import modal for CSV ID: ' . $csvId);
        
        if (!$csvId) {
            $this->logger->error('SquadexaAI ImportModal: Invalid CSV ID provided');
            $this->messageManager->addErrorMessage(__('Invalid CSV ID provided.'));
            return $this->createErrorPage();
        }

        try {
            $this->logger->info('SquadexaAI ImportModal: Loading CSV data for ID: ' . $csvId);
            $generatedCsv = $this->generatedCsvRepository->get($csvId);
            
            $this->logger->info('SquadexaAI ImportModal: CSV data loaded successfully');
            
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Squadkin_SquadexaAI::squadkin_squadexaai_generatedcsv');
            $resultPage->getConfig()->getTitle()->prepend(__('Import AI Products to Magento'));
            
            $this->logger->info('SquadexaAI ImportModal: Result page created successfully');
            
            // Pass data to the block
            $block = $resultPage->getLayout()->getBlock('import.modal.content');
            if ($block) {
                $block->setGeneratedCsv($generatedCsv);
                $this->logger->info('SquadexaAI ImportModal: Block data set successfully');
            } else {
                $this->logger->warning('SquadexaAI ImportModal: Block import.modal.content not found in layout');
            }
            
            $this->logger->info('SquadexaAI ImportModal: Import modal loaded successfully');
            return $resultPage;
            
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI ImportModal: Error loading CSV data: ' . $e->getMessage());
            $this->logger->error('SquadexaAI ImportModal: Exception trace: ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage(__('Unable to load CSV data: %1', $e->getMessage()));
            return $this->createErrorPage();
        }
    }

    /**
     * Create error page
     *
     * @return Page
     */
    private function createErrorPage(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Error'));
        return $resultPage;
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
