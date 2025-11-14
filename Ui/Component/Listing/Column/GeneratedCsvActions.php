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

/**
 * Class GeneratedCsvActions
 *
 * Provides action buttons for GeneratedCsv grid rows (Edit, Delete)
 * Follows Magento 2 coding standards
 */
class GeneratedCsvActions extends Column
{
    /**
     * URL path for delete action
     */
    const URL_PATH_DELETE = 'squadkin_squadexaai/generatedcsv/delete';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor
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
                $name = $this->getData('name');
                if (isset($item['generatedcsv_id'])) {
                    $item[$name] = [
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                self::URL_PATH_DELETE,
                                ['generatedcsv_id' => $item['generatedcsv_id']]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Generated CSV Record'),
                                'message' => __('Are you sure you want to delete this record and its associated files?')
                            ],
                            'post' => true
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}

