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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class AiProductActions extends Column
{
    public const URL_PATH_EDIT = 'squadkin_squadexaai/aiproduct/edit';
    public const URL_PATH_DELETE = 'squadkin_squadexaai/aiproduct/delete';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AiProductActions constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['aiproduct_id'])) {
                    // Check if product is created in Magento
                    $isCreatedInMagento = isset($item['is_created_in_magento']) &&
                                         ($item['is_created_in_magento'] == 1 || $item['is_created_in_magento'] === true || $item['is_created_in_magento'] === '1');
                    
                    $actions = [];
                    
                    // Determine if we should show "Edit" or "View"
                    // Logic:
                    // - If NOT created in Magento: Always show "Edit"
                    // - If created in Magento:
                    //   - Show "Edit" if AI data is newer than Magento product (regenerated after creation)
                    //   - Show "View" if AI data is older/equal to Magento product (already synced)
                    $showEdit = true;
                    $actionLabel = __('Edit');
                    
                    if ($isCreatedInMagento && isset($item['magento_product_id']) && !empty($item['magento_product_id'])) {
                        // Check if AI data is newer than Magento product
                        $aiDataIsNewer = $this->isAiDataNewerThanMagentoProduct(
                            $item['updated_at'] ?? null,
                            (int)$item['magento_product_id']
                        );
                        
                        if (!$aiDataIsNewer) {
                            // AI data is not newer, show "View" instead of "Edit"
                            $showEdit = false;
                            $actionLabel = __('View');
                        }
                    }
                    
                    // Always show Edit/View button
                    $actions['edit'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_EDIT,
                            [
                                'aiproduct_id' => $item['aiproduct_id']
                            ]
                        ),
                        'label' => $actionLabel
                    ];
                    
                    // Delete action is always available
                    $actions['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_DELETE,
                            [
                                'aiproduct_id' => $item['aiproduct_id']
                            ]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', $item['product_name'] ?? $item['name'] ?? ''),
                            'message' => __('Are you sure you want to delete the AI product "%1"?', $item['product_name'] ?? $item['name'] ?? '')
                        ]
                    ];
                    
                    // Only add "Create Product from AI Data" action if product is NOT created in Magento
                    if (!$isCreatedInMagento) {
                        $actions['create_product'] = [
                            'href' => '',
                            'label' => __('Create Product from AI Data'),
                            'callback' => true
                        ];
                    }
                    
                    // Add "Update Product in Magento" action if product is already created in Magento
                    if ($isCreatedInMagento && isset($item['magento_product_id']) && !empty($item['magento_product_id'])) {
                        $actions['update_product'] = [
                            'href' => '',
                            'label' => __('Update Product in Magento'),
                            'callback' => true
                        ];
                    }
                    
                    $item[$this->getData('name')] = $actions;
                }
            }
        }

        return $dataSource;
    }

    /**
     * Check if AI product data is newer than Magento product
     *
     * @param string|null $aiUpdatedAt
     * @param int $magentoProductId
     * @return bool
     */
    private function isAiDataNewerThanMagentoProduct(?string $aiUpdatedAt, int $magentoProductId): bool
    {
        if (!$aiUpdatedAt) {
            // If AI updated_at is not available, assume it's not newer
            return false;
        }

        try {
            $magentoProduct = $this->productRepository->getById($magentoProductId);
            $magentoUpdatedAt = $magentoProduct->getUpdatedAt();

            if (!$magentoUpdatedAt) {
                // If Magento product has no updated_at, assume AI data is newer
                return true;
            }

            $aiTimestamp = strtotime($aiUpdatedAt);
            $magentoTimestamp = strtotime($magentoUpdatedAt);

            // AI data is newer if its timestamp is greater than Magento product timestamp
            return $aiTimestamp > $magentoTimestamp;

        } catch (\Exception $e) {
            // If we can't load Magento product, log error and default to showing Edit
            $this->logger->warning('AiProductActions: Could not compare timestamps', [
                'magento_product_id' => $magentoProductId,
                'error' => $e->getMessage()
            ]);
            // Default to showing Edit if we can't determine
            return true;
        }
    }
}
