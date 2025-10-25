<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Psr\Log\LoggerInterface;

class CreateModal extends Action
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
     * @var AiProductRepositoryInterface
     */
    protected $aiProductRepository;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    protected $generatedCsvRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $jsonFactory
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $jsonFactory,
        AiProductRepositoryInterface $aiProductRepository,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonFactory = $jsonFactory;
        $this->aiProductRepository = $aiProductRepository;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Load AI product data for modal form
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $csvId = (int)$this->getRequest()->getParam('csv_id');
            
            if (!$csvId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Invalid CSV ID provided.')
                ]);
            }

            // Get generated CSV record
            $generatedCsv = $this->generatedCsvRepository->get($csvId);
            
            // Get AI products for this CSV
            $aiProducts = [];
            $searchResults = $this->aiProductRepository->getList();
            
            if ($searchResults && $searchResults->getItems()) {
                foreach ($searchResults->getItems() as $aiProduct) {
                    if ($aiProduct->getGeneratedcsvId() == $csvId) {
                        $aiProducts[] = [
                            'aiproduct_id' => $aiProduct->getAiproductId(),
                            'sku' => $aiProduct->getSku(),
                            'name' => $aiProduct->getName(),
                            'description' => $aiProduct->getDescription(),
                            'short_description' => $aiProduct->getShortDescription(),
                            'price' => $aiProduct->getPrice(),
                            'special_price' => $aiProduct->getSpecialPrice(),
                            'weight' => $aiProduct->getWeight(),
                            'qty' => $aiProduct->getQty(),
                            'category' => $aiProduct->getCategory(),
                            'status' => $aiProduct->getStatus(),
                            'visibility' => $aiProduct->getVisibility(),
                            'type' => $aiProduct->getType(),
                            'attribute_set' => $aiProduct->getAttributeSet(),
                            'tax_class' => $aiProduct->getTaxClass(),
                            'meta_title' => $aiProduct->getMetaTitle(),
                            'meta_description' => $aiProduct->getMetaDescription(),
                            'meta_keywords' => $aiProduct->getMetaKeywords(),
                            'url_key' => $aiProduct->getUrlKey()
                        ];
                    }
                }
            }

            if (empty($aiProducts)) {
                return $result->setData([
                    'success' => false,
                    'message' => __('No AI generated products found for this record.')
                ]);
            }

            return $result->setData([
                'success' => true,
                'generation_type' => $generatedCsv->getGenerationType(),
                'products' => $aiProducts
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error loading product creation modal: ' . $e->getMessage());
            
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while loading product data: %1', $e->getMessage())
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
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv');
    }
}

