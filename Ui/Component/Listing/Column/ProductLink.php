<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class ProductLink extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
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
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['magento_product_id'])) {
                    // Store the original value for display
                    $productId = $item['magento_product_id'];
                    
                    // Create the edit URL
                    $url = $this->urlBuilder->getUrl(
                        'catalog/product/edit',
                        ['id' => $productId]
                    );
                    
                    // Set the link data in the correct format for Magento_Ui/js/grid/columns/link
                    $item[$fieldName . '_link'] = $url;
                    $item[$fieldName] = $productId; // Keep the original value for display
                }
            }
        }

        return $dataSource;
    }
}
