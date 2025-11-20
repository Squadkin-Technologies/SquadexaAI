<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\Product\Edit\Button;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;

/**
 * Generate AI Data button
 */
class GenerateAiData extends Generic
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $product = $this->getProduct();
        
        if (!$product || !$product->getId()) {
            return [];
        }

        return [
            'label' => __('Generate/Edit AI Data'),
            'class' => 'action-secondary',
            'on_click' => 'window.squadexaAiGenerator && window.squadexaAiGenerator.openModal()',
            'sort_order' => 45,
            'data_attribute' => [
                'ui-id' => 'squadexa-ai-generate-button'
            ]
        ];
    }
}

