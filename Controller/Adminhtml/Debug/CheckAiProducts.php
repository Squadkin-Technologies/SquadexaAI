<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Debug;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Psr\Log\LoggerInterface;

class CheckAiProducts extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var GeneratedCsvRepositoryInterface
     */
    private $generatedCsvRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param GeneratedCsvRepositoryInterface $generatedCsvRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AiProductRepositoryInterface $aiProductRepository,
        GeneratedCsvRepositoryInterface $generatedCsvRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->aiProductRepository = $aiProductRepository;
        $this->generatedCsvRepository = $generatedCsvRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Execute debug check
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            // Get all AI products
            $allProductsSearch = $this->searchCriteriaBuilder->create();
            $allProducts = $this->aiProductRepository->getList($allProductsSearch);
            
            // Get all generated CSVs
            $allCsvsSearch = $this->searchCriteriaBuilder->create();
            $allCsvs = $this->generatedCsvRepository->getList($allCsvsSearch);
            
            $data = [
                'success' => true,
                'total_ai_products' => $allProducts->getTotalCount(),
                'total_csvs' => $allCsvs->getTotalCount(),
                'ai_products' => [],
                'csvs' => []
            ];
            
            // List AI products
            foreach ($allProducts->getItems() as $product) {
                $data['ai_products'][] = [
                    'id' => $product->getAiproductId(),
                    'csv_id' => $product->getGeneratedcsvId(),
                    'sku' => $product->getSku(),
                    'name' => $product->getName()
                ];
            }
            
            // List CSVs
            foreach ($allCsvs->getItems() as $csv) {
                $data['csvs'][] = [
                    'id' => $csv->getGeneratedcsvId(),
                    'input_file' => $csv->getInputFileName(),
                    'output_file' => $csv->getResponseFileName(),
                    'import_status' => $csv->getImportStatus()
                ];
            }
            
            return $resultJson->setData($data);
            
        } catch (\Exception $e) {
            $this->logger->error('Debug CheckAiProducts error: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        return $this->_authorization->isAllowed('Squadkin_SquadexaAI::GeneratedCsv_view');
    }
}
