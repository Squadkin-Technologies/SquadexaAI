<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\AiProduct\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Create Product from AI Button
 */
class CreateProductButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        $aiproductId = $this->getAiProductId();
        
        // Don't show button if product is already created in Magento
        if ($aiproductId && !$this->isCreatedInMagento()) {
            $data = [
                'label' => __('Create Product from AI'),
                'class' => 'primary',
                'on_click' => 'window.createProductFromAiModal && window.createProductFromAiModal.open(' . $aiproductId . ')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}

