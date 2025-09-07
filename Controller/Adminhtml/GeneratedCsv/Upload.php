<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Squadkin\AIAutoProductBuilder\Api\GeneratedCsvRepositoryInterface;
use Squadkin\AIAutoProductBuilder\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\AIAutoProductBuilder\Helper\FileManager;
use Squadkin\AIAutoProductBuilder\Service\AiGenerationOptionsService;

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
     * Upload constructor.
     *
     * @param Context $context
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param FileManager $fileManager
     * @param AiGenerationOptionsService $aiOptionsService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        FileManager $fileManager,
        AiGenerationOptionsService $aiOptionsService,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
        $this->aiOptionsService = $aiOptionsService;
        $this->logger = $logger;
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
        $this->logger->info('AIAutoProductBuilder Upload: Starting upload process');
        $this->logger->info('AIAutoProductBuilder Upload: Request method - ' . $this->getRequest()->getMethod());
        $this->logger->info('AIAutoProductBuilder Upload: POST data - ' . print_r($this->getRequest()->getPostValue(), true));
        $this->logger->info('AIAutoProductBuilder Upload: FILES data - ' . print_r($_FILES, true));
        
        try {
            // Check if file was uploaded
            if (!isset($_FILES['input_file']) || empty($_FILES['input_file']['tmp_name'])) {
                $this->logger->info('AIAutoProductBuilder Upload: No file uploaded');
                $this->messageManager->addErrorMessage(__('Please select a file to upload.'));
                return $resultRedirect;
            }

            $this->logger->info('AIAutoProductBuilder Upload: File received - ' . $_FILES['input_file']['name']);

            // Validate AI generation options
            $selectedAiOptions = $this->getRequest()->getParam('ai_options', []);
            $this->logger->info('AIAutoProductBuilder Upload: Selected AI options - ' . print_r($selectedAiOptions, true));
            
            if (empty($selectedAiOptions)) {
                $selectedAiOptions = $this->aiOptionsService->getDefaultSelectedOptions();
                $this->logger->info('AIAutoProductBuilder Upload: Using default AI options - ' . print_r($selectedAiOptions, true));
            }
            
            try {
                $validatedOptions = $this->aiOptionsService->validateSelectedOptions($selectedAiOptions);
                $this->logger->info('AIAutoProductBuilder Upload: AI options validated successfully');
            } catch (LocalizedException $e) {
                $this->logger->error('AIAutoProductBuilder Upload: AI options validation failed - ' . $e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect;
            }

            $fileData = $_FILES['input_file'];
            $this->logger->info('AIAutoProductBuilder Upload: Processing file - ' . $fileData['name']);

            // Validate uploaded file
            $this->fileManager->validateUploadedFile($fileData);
            $this->logger->info('AIAutoProductBuilder Upload: File validation passed');

            // Save input file
            $inputFileName = $this->fileManager->saveInputFile($fileData);
            $inputFilePath = '/var/AiBuilder/Input/' . $inputFileName;
            $this->logger->info('AIAutoProductBuilder Upload: File saved as - ' . $inputFileName);

            // Process file with AI API
            $this->messageManager->addNoticeMessage(__('Processing file with AI API...'));
            $this->logger->info('AIAutoProductBuilder Upload: Starting AI processing');
            $aiResponse = $this->fileManager->processWithAI($inputFileName, $validatedOptions);
            $this->logger->info('AIAutoProductBuilder Upload: AI processing completed. Response count: ' . count($aiResponse));

            // Filter AI response based on selected options
            $filteredAiResponse = $this->aiOptionsService->filterAiResponseBySelectedOptions($aiResponse, $validatedOptions);
            $this->logger->info('AIAutoProductBuilder Upload: Filtered response count: ' . count($filteredAiResponse));

            // Save AI response as output CSV
            $outputFileName = $this->fileManager->saveOutputFile($filteredAiResponse, $inputFileName);
            $outputFilePath = '/var/AiBuilder/Output/' . $outputFileName;
            $this->logger->info('AIAutoProductBuilder Upload: Output file saved as - ' . $outputFileName);

            // Save record to database
            $generatedCsv = $this->generatedCsvFactory->create();
            $generatedCsv->setInputFileName($inputFileName);
            $generatedCsv->setInputFilePath($inputFilePath);
            $generatedCsv->setResponseFileName($outputFileName);
            $generatedCsv->setResponseFilePath($outputFilePath);
            $generatedCsv->setTotalProductsCount(count($filteredAiResponse));

            $this->generatedCsvRepository->save($generatedCsv);
            $this->logger->info('AIAutoProductBuilder Upload: Database record saved with ID - ' . $generatedCsv->getGeneratedcsvId());

            // Save AI product data to database
            $this->fileManager->saveAiProductData($filteredAiResponse, (int)$generatedCsv->getGeneratedcsvId());
            $this->logger->info('AIAutoProductBuilder Upload: AI product data saved to database');

            $selectedOptionsLabels = $this->aiOptionsService->getSelectedOptionsWithLabels($validatedOptions);
            $successMessage = __('File processed successfully! Input file: %1, Output file: %2. %3 products saved to database with AI-generated fields: %4', 
                   $inputFileName, 
                   $outputFileName,
                   count($filteredAiResponse),
                   implode(', ', array_values($selectedOptionsLabels))
                );
            $this->messageManager->addSuccessMessage($successMessage);
            $this->logger->info('AIAutoProductBuilder Upload: Process completed successfully - ' . $successMessage);

        } catch (LocalizedException $e) {
            $this->logger->error('AIAutoProductBuilder Upload: LocalizedException - ' . $e->getMessage());
            $this->logger->error('AIAutoProductBuilder Upload: Exception trace - ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('AIAutoProductBuilder Upload: General Exception - ' . $e->getMessage());
            $this->logger->error('AIAutoProductBuilder Upload: Exception trace - ' . $e->getTraceAsString());
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
        return $this->_authorization->isAllowed('Squadkin_AIAutoProductBuilder::GeneratedCsv_save');
    }
} 