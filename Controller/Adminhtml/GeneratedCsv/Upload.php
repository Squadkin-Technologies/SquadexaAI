<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\SquadexaAI\Helper\FileManager;
use Squadkin\SquadexaAI\Service\SquadexaApiService;

class Upload extends Action
{
    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var GeneratedCsvInterfaceFactory
     */
    private $generatedCsvFactory;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * Upload constructor.
     *
     * @param Context $context
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory // phpcs:ignore
     * @param FileManager $fileManager
     * @param LoggerInterface $logger
     * @param SquadexaApiService $apiService
     * @param DirectoryList $directoryList
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Context $context,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        FileManager $fileManager,
        LoggerInterface $logger,
        SquadexaApiService $apiService,
        DirectoryList $directoryList,
        SessionManagerInterface $session
    ) {
        parent::__construct($context);
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
        $this->logger = $logger;
        $this->apiService = $apiService;
        $this->directoryList = $directoryList;
        $this->session = $session;
    }

    /**
     * Execute upload action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        try {
            $file = $this->getRequest()->getFiles('input_file');
            $hasFile = !empty($file);
            $fileNotEmpty = $hasFile && !empty($file['tmp_name']);
            // @codingStandardsIgnoreEnd
            if (!$hasFile || !$fileNotEmpty) {
                $this->messageManager->addErrorMessage(__('Please select a file to upload.'));
                return $resultRedirect;
            }
            // @codingStandardsIgnoreLine
            $fileData = $file;

            $this->fileManager->validateUploadedFile($fileData);

            $inputFileName = $this->fileManager->saveInputFile($fileData);
            $varDirectory = $this->directoryList->getPath('var');
            $inputFilePath = $varDirectory . '/AIProductCreator/Input/' . $inputFileName;
            $fullInputFilePath = $inputFilePath;

            $this->messageManager->addNoticeMessage(__('Creating batch job with AI API...'));
            
            $batchJobResponse = $this->apiService->createBatchJobWithFile($fullInputFilePath);
            
            $jobId = $batchJobResponse['job_id'] ?? null;
            $totalItems = $batchJobResponse['total_items'] ?? 0;
            $jobStatus = $batchJobResponse['status'] ?? 'pending';
            
            if (!$jobId) {
                throw new LocalizedException(__('Failed to create batch job: No job ID received from API.'));
            }

            // Save record to database with job_id
            $generatedCsv = $this->generatedCsvFactory->create();
            $generatedCsv->setInputFileName($inputFileName);
            $generatedCsv->setInputFilePath('/var/AIProductCreator/Input/' . $inputFileName);
            $generatedCsv->setResponseFileName(''); // Will be set when job completes
            $generatedCsv->setResponseFilePath(''); // Will be set when job completes
            $generatedCsv->setTotalProductsCount($totalItems);
            $generatedCsv->setGenerationType('csv');
            $generatedCsv->setJobId($jobId);
            $generatedCsv->setImportStatus('pending'); // Status will be updated during polling

            $this->generatedCsvRepository->save($generatedCsv);
            $this->logger->info(
                'SquadexaAI Upload: Database record saved with ID - ' .
                $generatedCsv->getGeneratedcsvId() . ', Job ID: ' . $jobId
            ); // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

            // Build URLs for guidance links
            $aiProductGridUrl = $this->getUrl('squadkin_squadexaai/aiproduct/index');
            $magentoImportUrl = $this->getUrl('adminhtml/import/index');
            
            // Create comprehensive success message with step-by-step guidance
            $successMessage = '<strong>Batch job created successfully!</strong><br/>';
            $successMessage .= '<p style="margin-top: 10px; margin-bottom: 5px;">';
            $successMessage .= '<strong>What\'s Next?</strong></p>';
            $successMessage .= '<p style="margin-bottom: 5px;">';
            $successMessage .= '✓ The response will be ready soon. ';
            $successMessage .= 'Once the response is ready, you can:</p>';
            $successMessage .= '<ul style="margin-left: 20px; margin-top: 5px; ';
            $successMessage .= 'margin-bottom: 10px;">';
            $successMessage .= '<li>View and edit each AI-generated product response</li>';
            $successMessage .= '<li>Create products one by one from the ';
            $successMessage .= '<a href="' . $aiProductGridUrl . '" target="_blank">';
            $successMessage .= '<strong>Squadexa AI - Products Data</strong></a> page</li>';
            $successMessage .= '</ul>';
            $successMessage .= '<p style="margin-bottom: 5px;">';
            $successMessage .= '<strong>Bulk Import Option:</strong></p>';
            $successMessage .= '<p style="margin-bottom: 5px;">';
            $successMessage .= 'If you want to import products in bulk:</p>';
            $successMessage .= '<ul style="margin-left: 20px; margin-top: 5px;">';
            $successMessage .= '<li>Download the Output CSV file from this page ';
            $successMessage .= 'once it\'s ready</li>';
            $successMessage .= '<li>Update it according to Magento\'s ';
            $successMessage .= 'import standard format</li>';
            $successMessage .= '<li>Import it using Magento\'s ';
            $successMessage .= '<a href="' . $magentoImportUrl . '" ';
            $successMessage .= 'target="_blank"><strong>Import Products</strong></a> ';
            $successMessage .= 'page</li>';
            $successMessage .= '</ul>';
            
            // Store HTML message in session to be displayed via block
            $this->session->setData(
                'squadexa_html_success_message',
                $successMessage
            );
            
            // Also add a simple text message for compatibility
            $this->messageManager->addSuccessMessage(__('Batch job created successfully! Job ID: %1', $jobId));
            $this->logger->info('SquadexaAI Upload: Batch job created - Job ID: ' . $jobId);

        } catch (LocalizedException $e) {
            $this->logger->error('SquadexaAI Upload: LocalizedException - ' . $e->getMessage());
            $this->logger->error('SquadexaAI Upload: Exception trace - ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Upload: General Exception - ' . $e->getMessage());
            $this->logger->error('SquadexaAI Upload: Exception trace - ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage(
                __('An error occurred while processing the file: %1', $e->getMessage())
            );
        }

        return $resultRedirect;
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
