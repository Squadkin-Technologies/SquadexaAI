<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Controller\Adminhtml\GeneratedCsv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Squadkin\AIAutoProductBuilder\Service\CsvValidationService;
use Squadkin\AIAutoProductBuilder\Service\AiGenerationOptionsService;
use Squadkin\AIAutoProductBuilder\Helper\FileManager;

class Validate extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CsvValidationService
     */
    private $csvValidationService;

    /**
     * @var AiGenerationOptionsService
     */
    private $aiOptionsService;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * Validate constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CsvValidationService $csvValidationService
     * @param AiGenerationOptionsService $aiOptionsService
     * @param FileManager $fileManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CsvValidationService $csvValidationService,
        AiGenerationOptionsService $aiOptionsService,
        FileManager $fileManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->csvValidationService = $csvValidationService;
        $this->aiOptionsService = $aiOptionsService;
        $this->fileManager = $fileManager;
    }

    /**
     * Execute CSV validation
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            // Check if file was uploaded
            if (!isset($_FILES['input_file']) || empty($_FILES['input_file']['tmp_name'])) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => __('Please select a file to upload.')->render()
                ]);
            }

            // Validate AI generation options
            $selectedAiOptions = $this->getRequest()->getParam('ai_options', []);
            if (empty($selectedAiOptions)) {
                $selectedAiOptions = $this->aiOptionsService->getDefaultSelectedOptions();
            }

            try {
                $validatedOptions = $this->aiOptionsService->validateSelectedOptions($selectedAiOptions);
            } catch (LocalizedException $e) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            $fileData = $_FILES['input_file'];

            // Validate uploaded file format
            $this->fileManager->validateUploadedFile($fileData);

            // Save file temporarily for validation
            $tempFileName = $this->fileManager->saveInputFile($fileData);
            $tempFilePath = 'AiBuilder/Input/' . $tempFileName;

            // Perform CSV validation
            $validationResult = $this->csvValidationService->validateCsvFile($tempFilePath);

            $responseData = [
                'success' => $validationResult['is_valid'],
                'temp_file' => $tempFileName,
                'validation_result' => [
                    'is_valid' => $validationResult['is_valid'],
                    'processed_rows' => $validationResult['processed_rows'],
                    'valid_rows' => $validationResult['valid_rows'],
                    'headers' => $validationResult['headers'],
                    'sample_data' => $validationResult['sample_data'],
                    'validation_messages' => $this->formatValidationMessages($validationResult['validation_messages'])
                ],
                'ai_options' => [
                    'selected' => $validatedOptions,
                    'labels' => $this->aiOptionsService->getSelectedOptionsWithLabels($validatedOptions)
                ]
            ];

            // If validation failed, generate error report
            if (!$validationResult['is_valid']) {
                try {
                    $errorReportFileName = $this->csvValidationService->generateErrorReport(
                        $validationResult['error_aggregator'],
                        $fileData['name']
                    );
                    $responseData['error_report'] = $errorReportFileName;
                } catch (LocalizedException $e) {
                    // Error report generation failed, but validation can continue
                }
            }

            return $resultJson->setData($responseData);

        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'error' => __('An error occurred while validating the file: %1', $e->getMessage())->render()
            ]);
        }
    }

    /**
     * Format validation messages for JSON response
     *
     * @param array $messages
     * @return array
     */
    private function formatValidationMessages(array $messages): array
    {
        $formattedMessages = [];
        foreach ($messages as $message) {
            if (is_object($message) && method_exists($message, 'render')) {
                $formattedMessages[] = $message->render();
            } else {
                $formattedMessages[] = (string)$message;
            }
        }
        return $formattedMessages;
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