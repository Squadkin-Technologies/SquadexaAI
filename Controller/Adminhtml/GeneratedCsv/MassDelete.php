<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Helper\FileManager;
use Squadkin\SquadexaAI\Model\ResourceModel\GeneratedCsv\CollectionFactory;

/**
 * Class MassDelete
 *
 * Handles mass deletion of GeneratedCsv records along with associated files
 * Follows Magento 2 coding standards and SOLID principles
 */
class MassDelete extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::squadkin_squadexaai_generatedcsv_delete';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param FileManager $fileManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        FileManager $fileManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * Execute mass delete action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        try {
            // Get filtered collection from UI grid
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();

            if ($collectionSize === 0) {
                $this->messageManager->addErrorMessage(__('Please select item(s) to delete.'));
                return $resultRedirect;
            }

            $deletedCount = 0;
            $fileErrors = [];
            $errorMessages = [];

            foreach ($collection as $generatedCsv) {
                try {
                    $generatedCsvId = (int)$generatedCsv->getGeneratedcsvId();

                    // Delete associated files before deleting database record
                    $fileDeletionResult = $this->fileManager->deleteGeneratedCsvFiles(
                        $generatedCsv->getInputFileName(),
                        $generatedCsv->getInputFilePath(),
                        $generatedCsv->getResponseFileName(),
                        $generatedCsv->getResponseFilePath()
                    );

                    // Collect file deletion errors
                    if (!empty($fileDeletionResult['errors'])) {
                        $fileErrors = array_merge($fileErrors, $fileDeletionResult['errors']);
                        $this->logger->warning('SquadexaAI MassDelete: Some files could not be deleted', [
                            'generatedcsv_id' => $generatedCsvId,
                            'errors' => $fileDeletionResult['errors']
                        ]);
                    }

                    // Delete database record
                    $this->generatedCsvRepository->deleteById($generatedCsvId);
                    $deletedCount++;

                } catch (LocalizedException $e) {
                    $errorMessages[] = __('Record ID %1: %2', $generatedCsvId, $e->getMessage());
                    $this->logger->error('SquadexaAI MassDelete: Localized exception', [
                        'generatedcsv_id' => $generatedCsvId ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                } catch (\Exception $e) {
                    $errorMessages[] = __('Record ID %1: %2', $generatedCsvId ?? 'unknown', $e->getMessage());
                    $this->logger->error('SquadexaAI MassDelete: Unexpected error', [
                        'generatedcsv_id' => $generatedCsvId ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Prepare and display messages
            if ($deletedCount > 0) {
                $message = __('A total of %1 record(s) have been deleted.', $deletedCount);
                
                if (!empty($fileErrors)) {
                    $message .= ' ' . __('Note: Some files could not be deleted.');
                }
                
                if (!empty($errorMessages)) {
                    $message .= ' ' . __('Errors occurred for some records: %1', implode('; ', $errorMessages));
                }
                
                $this->messageManager->addSuccessMessage($message);
            } else {
                $errorMessage = __('No records were deleted.');
                if (!empty($errorMessages)) {
                    $errorMessage .= ' ' . implode(' ', $errorMessages);
                }
                $this->messageManager->addErrorMessage($errorMessage);
            }

            $this->logger->info('SquadexaAI MassDelete: Mass deletion completed', [
                'total_selected' => $collectionSize,
                'deleted_count' => $deletedCount,
                'file_errors_count' => count($fileErrors)
            ]);

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->error('SquadexaAI MassDelete: Localized exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the records.'));
            $this->logger->error('SquadexaAI MassDelete: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $resultRedirect;
    }
}

