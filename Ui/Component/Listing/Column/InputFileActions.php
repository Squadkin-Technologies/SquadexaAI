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

class InputFileActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * InputFileActions constructor.
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
                if (isset($item['input_file_name']) && !empty($item['input_file_name'])) {
                    $item[$name] = [
                        'download' => [
                            'href' => $this->urlBuilder->getUrl(
                                'squadkin_squadexaai/generatedcsv/download',
                                [
                                    'file' => $item['input_file_name'],
                                    'type' => 'input'
                                ]
                            ),
                            'label' => __('Download Input File'),
                            'class' => 'action-download',
                            'target' => '_blank'
                        ]
                    ];
                } else {
                    $item[$name] = [
                        'label' => __('No File Available')
                    ];
                }
            }
        }

        return $dataSource;
    }
} 