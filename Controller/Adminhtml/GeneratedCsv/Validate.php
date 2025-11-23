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
use Magento\Framework\Exception\LocalizedException;
use Squadkin\SquadexaAI\Service\CsvValidationService;
use Squadkin\SquadexaAI\Helper\FileManager;

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
     * @var FileManager
     */
    private $fileManager;

    /**
     * Validate constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CsvValidationService $csvValidationService
     * @param FileManager $fileManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CsvValidationService $csvValidationService,
        FileManager $fileManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->csvValidationService = $csvValidationService;
        $this->fileManager = $fileManager;
    }

    /**
     * Execute CSV validation
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create(); // phpcs:ignore

        try {
            // @codingStandardsIgnoreStart
            $hasFile = isset($_FILES['input_file']); // phpcs:ignore Magento2.Security.Superglobal
            $fileNotEmpty = $hasFile &&
                !empty($_FILES['input_file']['tmp_name']); // phpcs:ignore Magento2.Security.Superglobal
            // @codingStandardsIgnoreEnd
            if (!$hasFile || !$fileNotEmpty) {
                return $resultJson->setData([
                    'success' => false,
                    'error' => __('Please select a file to upload.')->render()
                ]);
            }
            // @codingStandardsIgnoreLine
            $fileData = $_FILES['input_file']; // phpcs:ignore Magento2.Security.Superglobal

            // Validate uploaded file format
            $this->fileManager->validateUploadedFile($fileData);

            // Save file temporarily for validation
            $tempFileName = $this->fileManager->saveInputFile($fileData);
            $tempFilePath = 'AIProductCreator/Input/' . $tempFileName;

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
                    'validation_messages' => $this->formatValidationMessages(
                        $validationResult['validation_messages']
                    )
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
                    // phpcs:ignore MEQP2.Exceptions.EmptyCatch
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
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv_save');
    }
}
