<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\ProductGeneration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\SessionManagerInterface;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Squadkin\SquadexaAI\Logger\Logger as SquadexaLogger;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\SquadexaAI\Helper\FileManager;
use Psr\Log\LoggerInterface;

class Generate extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var SquadexaApiService
     */
    protected $apiService;

    /**
     * @var SquadexaLogger
     */
    protected $squadexaLogger;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    protected $generatedCsvRepository;

    /**
     * @var GeneratedCsvInterfaceFactory
     */
    protected $generatedCsvFactory;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param SquadexaApiService $apiService
     * @param SquadexaLogger $squadexaLogger
     * @param LoggerInterface $logger
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param FileManager $fileManager
     * @param Json $jsonSerializer
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        SquadexaApiService $apiService,
        SquadexaLogger $squadexaLogger,
        LoggerInterface $logger,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        FileManager $fileManager,
        Json $jsonSerializer,
        SessionManagerInterface $session
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->apiService = $apiService;
        $this->squadexaLogger = $squadexaLogger;
        $this->logger = $logger;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
        $this->jsonSerializer = $jsonSerializer;
        $this->session = $session;
        parent::__construct($context);
    }

    /**
     * Generate single product via AJAX
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $productName = $this->getRequest()->getParam('product_name');
            $primaryKeywords = $this->getRequest()->getParam('primary_keywords');
            $secondaryKeywords = $this->getRequest()->getParam('secondary_keywords', '');
            $includePricing = $this->getRequest()->getParam('include_pricing', false);

            // Validate required fields
            if (empty($productName) || empty($primaryKeywords)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product name and primary keywords are required.')
                ]);
            }

            $this->squadexaLogger->logApiRequest('Single product generation', [
                'product_name' => $productName,
                'primary_keywords' => $primaryKeywords,
                'secondary_keywords' => $secondaryKeywords,
                'include_pricing' => $includePricing
            ]);

            // Prepare data for API
            $productData = [
                'product_name' => $productName,
                'primary_keywords' => explode(',', $primaryKeywords),
                'secondary_keywords' => !empty($secondaryKeywords) ? explode(',', $secondaryKeywords) : [],
                'include_pricing' => (bool)$includePricing
            ];

            // Call API to generate product
            $apiResponse = $this->apiService->generateProduct($productData);

            $this->squadexaLogger->logApiResponse('Single product generation response', [
                'response_data' => $apiResponse
            ]);

            // For single product generation, skip CSV file creation and GeneratedCsv table entry
            // Save directly to AiProduct table
            $productArray = [$apiResponse];
            
            // Always set product_name from form data (required field, API response doesn't include it)
            $productArray[0]['product_name'] = $productName;
            
            // Also check for 'name' field as fallback if product_name is empty
            if (empty($productArray[0]['product_name']) && isset($productArray[0]['name'])) {
                $productArray[0]['product_name'] = $productArray[0]['name'];
            }
            
            // Add keywords from form if not in response
            if (!isset($productArray[0]['primary_keywords']) || empty($productArray[0]['primary_keywords'])) {
                $productArray[0]['primary_keywords'] = $primaryKeywords;
            }
            if (!isset($productArray[0]['secondary_keywords']) || empty($productArray[0]['secondary_keywords'])) {
                $productArray[0]['secondary_keywords'] = $secondaryKeywords;
            }
            
            // Save to AiProduct table with null generatedCsvId for single products
            $saveResult = $this->fileManager->saveAiProductData($productArray, null, 'single');
            $isUpdate = $saveResult['updated_count'] > 0;

            // Build URLs for guidance
            $aiProductGridUrl = $this->getUrl('squadkin_squadexaai/aiproduct/index');
            $fieldMappingConfigUrl = $this->getUrl('adminhtml/system_config/edit/section/squadexaiproductcreator', ['_fragment' => 'squadexaiproductcreator_field_mapping-link']);
            
            // Create comprehensive success message with step-by-step guidance
            if ($isUpdate) {
                $successMessage = '<strong>Product updated successfully!</strong><br/>' .
                    '<p style="margin-top: 10px; margin-bottom: 5px;">An existing product with the same name was found and updated with the latest AI-generated data.</p>' .
                    '<p style="margin-top: 10px; margin-bottom: 5px;"><strong>What\'s Next?</strong></p>' .
                    '<p style="margin-bottom: 5px;">You have been redirected to the <a href="' . $aiProductGridUrl . '" target="_blank"><strong>Squadexa AI - Products Data</strong></a> grid. From here you can:</p>' .
                    '<ul style="margin-left: 20px; margin-top: 5px; margin-bottom: 10px;">' .
                    '<li>View and edit the updated AI-generated product response</li>' .
                    '<li>If the product is already created in Magento, use "Update Product in Magento" to sync the latest changes</li>' .
                    '<li>Or create a new product using the "Create Product from AI Data" action</li>' .
                    '</ul>' .
                    '<p style="margin-bottom: 5px;"><strong>Important:</strong> Before creating or updating products, make sure you have configured field mappings in <a href="' . $fieldMappingConfigUrl . '" target="_blank"><strong>System Configuration → Field Mapping</strong></a>.</p>';
            } else {
                $successMessage = '<strong>Product generated successfully!</strong><br/>' .
                    '<p style="margin-top: 10px; margin-bottom: 5px;"><strong>What\'s Next?</strong></p>' .
                    '<p style="margin-bottom: 5px;">You have been redirected to the <a href="' . $aiProductGridUrl . '" target="_blank"><strong>Squadexa AI - Products Data</strong></a> grid. From here you can:</p>' .
                    '<ul style="margin-left: 20px; margin-top: 5px; margin-bottom: 10px;">' .
                    '<li>View and edit each AI-generated product response</li>' .
                    '<li>Create products one by one using the "Create Product from AI Data" action in the grid</li>' .
                    '<li>Or edit individual products and create them from the edit page</li>' .
                    '</ul>' .
                    '<p style="margin-bottom: 5px;"><strong>Important:</strong> Before creating products, make sure you have configured field mappings in <a href="' . $fieldMappingConfigUrl . '" target="_blank"><strong>System Configuration → Field Mapping</strong></a>.</p>' .
                    '<p style="margin-bottom: 5px;">Field mappings tell the system which Magento product attributes to use for each AI-generated field.</p>';
            }
            
            // Store HTML message in session to be displayed on AI Product grid page after redirect
            $this->session->setData('squadexa_html_success_message', $successMessage);

            // Redirect to Squadexa AI - Products Data grid for single product generation
            $redirectUrl = $this->getUrl('squadkin_squadexaai/aiproduct/index');

            return $result->setData([
                'success' => true,
                'message' => __('Product generated successfully!'),
                'data' => $apiResponse,
                'redirect_url' => $redirectUrl
            ]);

        } catch (LocalizedException $e) {
            $this->logger->error('SquadexaAI Single Product Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Single Product Generation Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while generating the product: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user has permission
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::squadexaiproductcreator');
    }
}

