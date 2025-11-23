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
use Squadkin\SquadexaAI\Model\Config\Source\ImportStatus;

class ImportActions extends Column
{
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
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['generatedcsv_id'])) {
                    $item[$this->getData('name')] = $this->getImportActions($item);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get import actions based on current status
     *
     * @param array $item
     * @return array
     */
    private function getImportActions(array $item): array
    {
        $actions = [];
        $status = $item['import_status'] ?? ImportStatus::STATUS_PENDING;
        $csvId = $item['generatedcsv_id'];

        switch ($status) {
            case ImportStatus::STATUS_PENDING:
                // Show Import button for pending items
                $actions[] = [
                    'href' => $this->urlBuilder->getUrl(
                        'squadkin_squadexaai/generatedcsv/importmodal',
                        ['id' => $csvId]
                    ),
                    'label' => __('Import to Magento'),
                    'class' => 'action-import',
                    'target' => '_blank'
                ];
                break;

            case ImportStatus::STATUS_PROCESSING:
                // Allow access to import page even during processing to see progress
                $actions[] = [
                    'href' => $this->urlBuilder->getUrl(
                        'squadkin_squadexaai/generatedcsv/importmodal',
                        ['id' => $item['generatedcsv_id']]
                    ),
                    'label' => __('View Processing Status'),
                    'class' => 'action-processing-view',
                    'title' => __('Click to view import progress and status'),
                    'target' => '_blank'
                ];
                $actions[] = [
                    'href' => 'javascript:location.reload()',
                    'label' => __('Refresh Status'),
                    'class' => 'action-refresh',
                    'title' => __('Click to refresh the page and check current status')
                ];
                break;

            case ImportStatus::STATUS_COMPLETED:
                // Show view imported products link
                $actions[] = [
                    'href' => $this->urlBuilder->getUrl(
                        'squadkin_squadexaai/generatedcsv/viewimported',
                        ['id' => $csvId]
                    ),
                    'label' => __('View Imported Products'),
                    'class' => 'action-view-imported'
                ];
                
                // Show re-import option
                $actions[] = [
                    'href' => $this->urlBuilder->getUrl(
                        'squadkin_squadexaai/generatedcsv/importmodal',
                        ['id' => $csvId]
                    ),
                    'label' => __('Re-Import'),
                    'class' => 'action-reimport',
                    'target' => '_blank'
                ];
                break;

            case ImportStatus::STATUS_FAILED:
                // Show error details and retry option
                $actions[] = [
                    'href' => $this->urlBuilder->getUrl(
                        'squadkin_squadexaai/generatedcsv/importerrors',
                        ['id' => $csvId]
                    ),
                    'label' => __('View Error Details'),
                    'class' => 'action-view-errors',
                    'target' => '_blank'
                ];
                
                $actions[] = [
                    'href' => $this->urlBuilder->getUrl(
                        'squadkin_squadexaai/generatedcsv/importmodal',
                        ['id' => $csvId]
                    ),
                    'label' => __('Retry Import'),
                    'class' => 'action-retry-import',
                    'target' => '_blank'
                ];
                break;
        }

        return $actions;
    }
}
