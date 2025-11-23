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
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Helper\FileManager;

/**
 * Class Delete
 *
 * Handles single GeneratedCsv record deletion along with associated files
 * Follows Magento 2 coding standards and SOLID principles
 */
class Delete extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::squadkin_squadexaai_generatedcsv_delete';

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
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param FileManager $fileManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        FileManager $fileManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * Execute delete action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        $generatedCsvId = $this->getRequest()->getParam('generatedcsv_id');

        if (!$generatedCsvId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a record to delete.'));
            return $resultRedirect;
        }

        try {
            // Load the GeneratedCsv record to get file information
            $generatedCsv = $this->generatedCsvRepository->get((int)$generatedCsvId);

            // Delete associated files before deleting database record
            $fileDeletionResult = $this->fileManager->deleteGeneratedCsvFiles(
                $generatedCsv->getInputFileName(),
                $generatedCsv->getInputFilePath(),
                $generatedCsv->getResponseFileName(),
                $generatedCsv->getResponseFilePath()
            );

            // Log file deletion results
            if (!empty($fileDeletionResult['errors'])) {
                $this->logger->warning('SquadexaAI Delete: Some files could not be deleted', [
                    'generatedcsv_id' => $generatedCsvId,
                    'errors' => $fileDeletionResult['errors']
                ]);
            }

            // Delete database record
            $this->generatedCsvRepository->deleteById((int)$generatedCsvId);

            // Prepare success message
            $message = __('The record has been deleted.');
            if (!empty($fileDeletionResult['errors'])) {
                $message .= ' ' . __(
                    'Note: Some files could not be deleted: %1',
                    implode(', ', $fileDeletionResult['errors'])
                );
            }

            $this->messageManager->addSuccessMessage($message);
            $this->logger->info('SquadexaAI Delete: GeneratedCsv record deleted successfully', [
                'generatedcsv_id' => $generatedCsvId,
                'input_file_deleted' => $fileDeletionResult['input_deleted'],
                'output_file_deleted' => $fileDeletionResult['output_deleted']
            ]);

        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('We can\'t find a record to delete.'));
            $this->logger->error('SquadexaAI Delete: Record not found', [
                'generatedcsv_id' => $generatedCsvId,
                'error' => $e->getMessage()
            ]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->error('SquadexaAI Delete: Localized exception', [
                'generatedcsv_id' => $generatedCsvId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the record.'));
            $this->logger->error('SquadexaAI Delete: Unexpected error', [
                'generatedcsv_id' => $generatedCsvId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $resultRedirect;
    }
}
