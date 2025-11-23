<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\ProductGeneration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\SessionManagerInterface;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Squadkin\SquadexaAI\Logger\Logger as SquadexaLogger;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\AiProductInterfaceFactory;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\SquadexaAI\Helper\FileManager;
use Psr\Log\LoggerInterface;

class Single extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

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
     * @var AiProductRepositoryInterface
     */
    protected $aiProductRepository;

    /**
     * @var AiProductInterfaceFactory
     */
    protected $aiProductFactory;

    /**
     * @var Json
     */
    protected $jsonSerializer;

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
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $jsonFactory
     * @param SquadexaApiService $apiService
     * @param SquadexaLogger $squadexaLogger
     * @param LoggerInterface $logger
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param AiProductInterfaceFactory $aiProductFactory
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param FileManager $fileManager
     * @param Json $jsonSerializer
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $jsonFactory,
        SquadexaApiService $apiService,
        SquadexaLogger $squadexaLogger,
        LoggerInterface $logger,
        AiProductRepositoryInterface $aiProductRepository,
        AiProductInterfaceFactory $aiProductFactory,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        FileManager $fileManager,
        Json $jsonSerializer,
        SessionManagerInterface $session
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->apiService = $apiService;
        $this->squadexaLogger = $squadexaLogger;
        $this->logger = $logger;
        $this->aiProductRepository = $aiProductRepository;
        $this->aiProductFactory = $aiProductFactory;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->fileManager = $fileManager;
        $this->jsonSerializer = $jsonSerializer;
        $this->session = $session;
        parent::__construct($context);
    }

    /**
     * Index action - Display unified product generation page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__("Product Generation"));
        return $resultPage;
    }

    /**
     * Generate single product via AJAX
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function generate()
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
            $saveResult = $this->fileManager->saveAiProductData(
                $productArray,
                null,
                'single'
            );
            $isUpdate = $saveResult['updated_count'] > 0;

            // Build URLs for guidance
            $aiProductGridUrl = $this->getUrl('squadkin_squadexaai/aiproduct/index');
            $fieldMappingConfigUrl = $this->getUrl(
                'adminhtml/system_config/edit/section/squadexaiproductcreator',
                ['_fragment' => 'squadexaiproductcreator_field_mapping-link']
            );
            
            // Create comprehensive success message with step-by-step guidance
            if ($isUpdate) {
                $successMessage = '<strong>Product updated successfully!</strong><br/>' .
                    '<p style="margin-top: 10px; margin-bottom: 5px;">' .
                    'An existing product with the same name was found and updated ' .
                    'with the latest AI-generated data.</p>' .
                    '<p style="margin-top: 10px; margin-bottom: 5px;"><strong>What\'s Next?</strong></p>' .
                    '<p style="margin-bottom: 5px;">You can now view this product in the ' .
                    '<a href="' . $aiProductGridUrl . '" target="_blank">' .
                    '<strong>Squadexa AI - Products Data</strong></a> grid. From there you can:</p>' .
                    '<ul style="margin-left: 20px; margin-top: 5px; margin-bottom: 10px;">' .
                    '<li>View and edit the updated AI-generated product response</li>' .
                    '<li>If the product is already created in Magento, use ' .
                    '"Update Product in Magento" to sync the latest changes</li>' .
                    '<li>Or create a new product using the "Create Product from AI Data" action</li>' .
                    '</ul>' .
                    '<p style="margin-bottom: 5px;"><strong>Important:</strong> ' .
                    'Before creating or updating products, make sure you have configured ' .
                    'field mappings in <a href="' . $fieldMappingConfigUrl . '" target="_blank">' .
                    '<strong>System Configuration → Field Mapping</strong></a>.</p>';
            } else {
                $successMessage = '<strong>Product generated successfully!</strong><br/>' .
                    '<p style="margin-top: 10px; margin-bottom: 5px;"><strong>What\'s Next?</strong></p>' .
                    '<p style="margin-bottom: 5px;">You can now view this product in the ' .
                    '<a href="' . $aiProductGridUrl . '" target="_blank">' .
                    '<strong>Squadexa AI - Products Data</strong></a> grid. From there you can:</p>' .
                    '<ul style="margin-left: 20px; margin-top: 5px; margin-bottom: 10px;">' .
                    '<li>View and edit the AI-generated product response</li>' .
                    '<li>Create the product using the "Create Product from AI Data" ' .
                    'action in the grid</li>' .
                    '<li>Or edit the product and create it from the edit page</li>' .
                    '</ul>' .
                    '<p style="margin-bottom: 5px;"><strong>Important:</strong> ' .
                    'Before creating the product, make sure you have configured field mappings ' .
                    'in <a href="' . $fieldMappingConfigUrl . '" target="_blank">' .
                    '<strong>System Configuration → Field Mapping</strong></a>.</p>' .
                    '<p style="margin-bottom: 5px;">' .
                    'Field mappings tell the system which Magento product attributes ' .
                    'to use for each AI-generated field.</p>';
            }
            
            // Store HTML message in session to be displayed on AI Product grid page after redirect
            $this->session->setData('squadexa_html_success_message', $successMessage);

            return $result->setData([
                'success' => true,
                'message' => __('Product generated successfully!'),
                'data' => $apiResponse
            ]); // phpcs:ignore

        } catch (LocalizedException $e) {
            $this->logger->error(
                'SquadexaAI Single Product Generation Error',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
            
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
     * Get products grid (both single and CSV generated)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function grid()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $collection = $this->aiProductRepository->getList();
            $records = [];
            
            // Check if collection exists and has items
            if ($collection && $collection->getItems()) {
                foreach ($collection->getItems() as $item) {
                    $records[] = [
                        'id' => $item->getAiproductId(),
                        'product_name' => $item->getProductName(),
                        'primary_keywords' => $item->getPrimaryKeywords(),
                        'secondary_keywords' => $item->getSecondaryKeywords(),
                        'type' => 'Single', // Can be extended to differentiate
                        'status' => $item->getStatus(),
                        'status_text' => ucfirst($item->getStatus()),
                        'created_at' => date('M d, Y H:i', strtotime($item->getCreatedAt()))
                    ];
                }
                
                // Sort by creation date descending only if we have records
                if (!empty($records)) {
                    usort($records, function ($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });
                }
            }
            
            return $result->setData([
                'success' => true,
                'data' => $records
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Products Grid Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty data instead of error when table doesn't exist or is empty
            return $result->setData([
                'success' => true,
                'data' => []
            ]);
        }
    }

    /**
     * Download single product result
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function download()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $result->setPath('*/*/index');
        
        try {
            $id = $this->getRequest()->getParam('id');
            
            if (!$id) {
                $this->messageManager->addErrorMessage(__('Product ID is required.'));
                return $result;
            }
            
            $aiProduct = $this->aiProductRepository->get($id);
            
            if (!$aiProduct->getAiproductId()) {
                $this->messageManager->addErrorMessage(__('Product not found.'));
                return $result;
            }
            
            // Create download file
            $response = $this->jsonSerializer->unserialize($aiProduct->getAiResponse());
            $filename = 'single_product_' . $aiProduct->getProductName() . '_' . date('Y-m-d_H-i-s') . '.json';
            
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $this->getResponse()->setBody($this->jsonSerializer->serialize($response));
            
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Single Product Download Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->messageManager->addErrorMessage(__('Error downloading product: %1', $e->getMessage()));
        }
        
        return $result;
    }

    /**
     * Delete single product result
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function delete()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $id = $this->getRequest()->getParam('id');
            
            if (!$id) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product ID is required.')
                ]);
            }
            
            $aiProduct = $this->aiProductRepository->get($id);
            
            if (!$aiProduct->getAiproductId()) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Product not found.')
                ]);
            }
            
            $this->aiProductRepository->delete($aiProduct);
            
            return $result->setData([
                'success' => true,
                'message' => __('Product deleted successfully.')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI Single Product Delete Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $result->setData([
                'success' => false,
                'message' => __('Error deleting product: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::product_generation');
    }
}
