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
use Squadkin\SquadexaAI\Service\AiGenerationOptionsService;
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
     * @var AiGenerationOptionsService
     */
    private $aiOptionsService;

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
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param FileManager $fileManager
     * @param AiGenerationOptionsService $aiOptionsService
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
        AiGenerationOptionsService $aiOptionsService,
        LoggerInterface $logger,
        SquadexaApiService $apiService,
        DirectoryList $directoryList,
        SessionManagerInterface $session
    ) {
        parent::__construct($context);
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
        $this->aiOptionsService = $aiOptionsService;
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

        // Add debug logging
        $this->logger->info('SquadexaAI Upload: Starting upload process');
        $this->logger->info('SquadexaAI Upload: Request method - ' . $this->getRequest()->getMethod());
        $this->logger->info('SquadexaAI Upload: POST data - ' . print_r($this->getRequest()->getPostValue(), true));
        $this->logger->info('SquadexaAI Upload: FILES data - ' . print_r($_FILES, true));
        
        try {
            // Check if file was uploaded
            if (!isset($_FILES['input_file']) || empty($_FILES['input_file']['tmp_name'])) {
                $this->logger->info('SquadexaAI Upload: No file uploaded');
                $this->messageManager->addErrorMessage(__('Please select a file to upload.'));
                return $resultRedirect;
            }

            $this->logger->info('SquadexaAI Upload: File received - ' . $_FILES['input_file']['name']);

            // Validate AI generation options
            $selectedAiOptions = $this->getRequest()->getParam('ai_options', []);
            $this->logger->info('SquadexaAI Upload: Selected AI options - ' . print_r($selectedAiOptions, true));
            
            if (empty($selectedAiOptions)) {
                $selectedAiOptions = $this->aiOptionsService->getDefaultSelectedOptions();
                $this->logger->info('SquadexaAI Upload: Using default AI options - ' . print_r($selectedAiOptions, true));
            }
            
            try {
                $validatedOptions = $this->aiOptionsService->validateSelectedOptions($selectedAiOptions);
                $this->logger->info('SquadexaAI Upload: AI options validated successfully');
            } catch (LocalizedException $e) {
                $this->logger->error('SquadexaAI Upload: AI options validation failed - ' . $e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect;
            }

            $fileData = $_FILES['input_file'];
            $this->logger->info('SquadexaAI Upload: Processing file - ' . $fileData['name']);

            // Validate uploaded file
            $this->fileManager->validateUploadedFile($fileData);
            $this->logger->info('SquadexaAI Upload: File validation passed');

            // Save input file first
            $inputFileName = $this->fileManager->saveInputFile($fileData);
            $varDirectory = $this->directoryList->getPath('var');
            $inputFilePath = $varDirectory . '/AIProductCreator/Input/' . $inputFileName;
            $fullInputFilePath = $inputFilePath;
            
            $this->logger->info('SquadexaAI Upload: File saved as - ' . $inputFileName);
            $this->logger->info('SquadexaAI Upload: Full file path - ' . $fullInputFilePath);

            // Create batch job via API
            $this->messageManager->addNoticeMessage(__('Creating batch job with AI API...'));
            $this->logger->info('SquadexaAI Upload: Creating batch job');
            
            $batchJobResponse = $this->apiService->createBatchJobWithFile($fullInputFilePath);
            
            $jobId = $batchJobResponse['job_id'] ?? null;
            $totalItems = $batchJobResponse['total_items'] ?? 0;
            $jobStatus = $batchJobResponse['status'] ?? 'pending';
            
            if (!$jobId) {
                throw new LocalizedException(__('Failed to create batch job: No job ID received from API.'));
            }

            $this->logger->info('SquadexaAI Upload: Batch job created successfully', [
                'job_id' => $jobId,
                'total_items' => $totalItems,
                'status' => $jobStatus
            ]);

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
            $this->logger->info('SquadexaAI Upload: Database record saved with ID - ' . $generatedCsv->getGeneratedcsvId() . ', Job ID: ' . $jobId);

            // Build URLs for guidance links
            $aiProductGridUrl = $this->getUrl('squadkin_squadexaai/aiproduct/index');
            $magentoImportUrl = $this->getUrl('adminhtml/import/index');
            
            // Create comprehensive success message with step-by-step guidance
            $successMessage = '<strong>Batch job created successfully!</strong><br/>' .
                '<p style="margin-top: 10px; margin-bottom: 5px;"><strong>What\'s Next?</strong></p>' .
                '<p style="margin-bottom: 5px;">✓ The response will be ready soon. Once the response is ready, you can:</p>' .
                '<ul style="margin-left: 20px; margin-top: 5px; margin-bottom: 10px;">' .
                '<li>View and edit each AI-generated product response</li>' .
                '<li>Create products one by one from the <a href="' . $aiProductGridUrl . '" target="_blank"><strong>Squadexa AI - Products Data</strong></a> page</li>' .
                '</ul>' .
                '<p style="margin-bottom: 5px;"><strong>Bulk Import Option:</strong></p>' .
                '<p style="margin-bottom: 5px;">If you want to import products in bulk:</p>' .
                '<ul style="margin-left: 20px; margin-top: 5px;">' .
                '<li>Download the Output CSV file from this page once it\'s ready</li>' .
                '<li>Update it according to Magento\'s import standard format</li>' .
                '<li>Import it using Magento\'s <a href="' . $magentoImportUrl . '" target="_blank"><strong>Import Products</strong></a> page</li>' .
                '</ul>';
            
            // Store HTML message in session to be displayed via block
            $this->session->setData('squadexa_html_success_message', $successMessage);
            
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