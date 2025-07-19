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
use Squadkin\AIAutoProductBuilder\Api\GeneratedCsvRepositoryInterface;
use Squadkin\AIAutoProductBuilder\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\AIAutoProductBuilder\Helper\FileManager;

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
     * Upload constructor.
     *
     * @param Context $context
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param FileManager $fileManager
     */
    public function __construct(
        Context $context,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        FileManager $fileManager
    ) {
        parent::__construct($context);
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
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
            // Check if file was uploaded
            if (!isset($_FILES['input_file']) || empty($_FILES['input_file']['tmp_name'])) {
                $this->messageManager->addErrorMessage(__('Please select a file to upload.'));
                return $resultRedirect;
            }

            $fileData = $_FILES['input_file'];

            // Validate uploaded file
            $this->fileManager->validateUploadedFile($fileData);

            // Save input file
            $inputFileName = $this->fileManager->saveInputFile($fileData);
            $inputFilePath = '/var/AiBuilder/Input/' . $inputFileName;

            // Process file with AI API
            $this->messageManager->addNoticeMessage(__('Processing file with AI API...'));
            $aiResponse = $this->fileManager->processWithAI($inputFileName);

            // Save AI response as output CSV
            $outputFileName = $this->fileManager->saveOutputFile($aiResponse, $inputFileName);
            $outputFilePath = '/var/AiBuilder/Output/' . $outputFileName;

            // Save record to database
            $generatedCsv = $this->generatedCsvFactory->create();
            $generatedCsv->setInputFileName($inputFileName);
            $generatedCsv->setInputFilePath($inputFilePath);
            $generatedCsv->setResponseFileName($outputFileName);
            $generatedCsv->setResponseFilePath($outputFilePath);

            $this->generatedCsvRepository->save($generatedCsv);

            // Save AI product data to database
            $this->fileManager->saveAiProductData($aiResponse, (int)$generatedCsv->getGeneratedcsvId());

            $this->messageManager->addSuccessMessage(
                __('File processed successfully! Input file: %1, Output file: %2. %3 products saved to database.', 
                   $inputFileName, 
                   $outputFileName,
                   count($aiResponse)
                )
            );

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
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