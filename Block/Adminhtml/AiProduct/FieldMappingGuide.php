<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\AiProduct;

use Magento\Backend\Block\Template;
use Squadkin\SquadexaAI\Helper\FieldMapping;

/**
 * Block to display field mapping guide message on AI Product grid
 */
class FieldMappingGuide extends Template
{
    /**
     * @var FieldMapping
     */
    private $fieldMappingHelper;

    /**
     * @param Template\Context $context
     * @param FieldMapping $fieldMappingHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        FieldMapping $fieldMappingHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->fieldMappingHelper = $fieldMappingHelper;
    }

    /**
     * Check if field mappings are configured
     *
     * @return bool
     */
    public function hasFieldMappings(): bool
    {
        $mappings = $this->fieldMappingHelper->getFieldMappings();
        return !empty($mappings);
    }

    /**
     * Get field mapping configuration URL
     *
     * @return string
     */
    public function getFieldMappingConfigUrl(): string
    {
        return $this->getUrl(
            'adminhtml/system_config/edit/section/squadexaiproductcreator',
            ['_fragment' => 'squadexaiproductcreator_field_mapping-link']
        );
    }
}
