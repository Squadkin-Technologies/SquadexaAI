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

class AiProductActions extends Column
{
    const URL_PATH_EDIT = 'squadkin_squadexaai/aiproduct/edit';
    const URL_PATH_DELETE = 'squadkin_squadexaai/aiproduct/delete';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * AiProductActions constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
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
                    
                    $actions = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'aiproduct_id' => $item['aiproduct_id']
                                ]
                            ),
                            'label' => $isCreatedInMagento ? __('View') : __('Edit')
                        ],
                        'delete' => [
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
                        ]
                    ];
                    
                    // Only add "Create Product from AI" action if product is NOT created in Magento
                    if (!$isCreatedInMagento) {
                        $actions['create_product'] = [
                            'href' => '',
                            'label' => __('Create Product from AI'),
                            'callback' => true
                        ];
                    }
                    
                    $item[$this->getData('name')] = $actions;
                }
            }
        }

        return $dataSource;
    }
} 