<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ProductCreateActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var AiProductRepositoryInterface
     */
    private $aiProductRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param AiProductRepositoryInterface $aiProductRepository
     * @param LoggerInterface $logger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        AiProductRepositoryInterface $aiProductRepository,
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->aiProductRepository = $aiProductRepository;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['generatedcsv_id'])) {
                    $item[$this->getData('name')] = $this->getProductActions($item);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get product create/delete actions based on current status
     *
     * @param array $item
     * @return array
     */
    private function getProductActions(array $item): array
    {
        $actions = [];
        $csvId = (int)$item['generatedcsv_id']; // Cast to int as grid data comes as string 
        $generationType = $item['generation_type'] ?? 'csv';

        try {
            // Check if any products from this CSV have been created in Magento
            $hasCreatedProducts = $this->hasProductsCreatedInMagento($csvId);

            if ($hasCreatedProducts) {
                // Show Delete button - removes from both AI table and Magento catalog
                $actions[] = [
                    'href' => '#',
                    'label' => __('Delete Products'),
                    'class' => 'action-delete',
                    'data-mage-init' => json_encode([
                        'Squadkin_SquadexaAI/js/grid/delete-products' => [
                            'csvId' => $csvId,
                            'deleteUrl' => $this->urlBuilder->getUrl(
                                'squadkin_squadexaai/product/massdelete',
                                ['csv_id' => $csvId]
                            )
                        ]
                    ])
                ];
            } else {
                // Show Create Product button - opens modal with form
                $actions[] = [
                    'href' => '#',
                    'label' => $generationType === 'single' ? __('Create Product') : __('Create Products'),
                    'class' => 'action-create-product',
                    'data-mage-init' => json_encode([
                        'Squadkin_SquadexaAI/js/grid/create-product-modal' => [
                            'csvId' => $csvId,
                            'generationType' => $generationType,
                            'modalUrl' => $this->urlBuilder->getUrl(
                                'squadkin_squadexaai/product/createmodal',
                                ['csv_id' => $csvId]
                            ),
                            'createUrl' => $this->urlBuilder->getUrl(
                                'squadkin_squadexaai/product/create',
                                ['csv_id' => $csvId]
                            )
                        ]
                    ])
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Error loading product create actions: ' . $e->getMessage());
        }

        return $actions;
    }

    /**
     * Check if any products from this CSV have been created in Magento
     *
     * @param int $csvId
     * @return bool
     */
    private function hasProductsCreatedInMagento(int $csvId): bool
    {
        try {
            // Build search criteria to filter by CSV ID and is_created_in_magento
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('generatedcsv_id', $csvId, 'eq')
                ->addFilter('is_created_in_magento', 1, 'eq')
                ->setPageSize(1) // We only need to know if at least one exists
                ->create();
            
            $searchResults = $this->aiProductRepository->getList($searchCriteria);
            
            return $searchResults->getTotalCount() > 0;
        } catch (\Exception $e) {
            $this->logger->error('Error checking created products: ' . $e->getMessage());
            return false;
        }
    }
}

