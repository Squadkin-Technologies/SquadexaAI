<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Plugin\Product\Edit;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Add "Update from AI" button to product edit page
 */
class AddUpdateFromAiButton
{
    /**
     * Add button to product edit page
     *
     * @param Generic $subject
     * @param array $result
     * @return array
     */
    public function afterGetButtonData(Generic $subject, array $result): array
    {
        // Only add if product exists (edit mode)
        // Use getProduct() method which exists in Generic button class
        try {
            $product = $subject->getProduct();
            if ($product && $product->getId()) {
                $productId = $product->getId();
                
                // Add "Generate/Edit AI Data" button
                $result['squadexa_ai_generate'] = [
                    'label' => __('Generate/Edit AI Data'),
                    'class' => 'action-secondary',
                    'on_click' => 'window.squadexaAiGenerator && window.squadexaAiGenerator.openModal()',
                    'sort_order' => 45,
                    'data_attribute' => [
                        'ui-id' => 'squadexa-ai-generate-button'
                    ]
                ];
                
                // Keep existing "Update from AI" button for backward compatibility
                $result['update_from_ai'] = [
                    'label' => __('Update from AI'),
                    'class' => 'action-secondary',
                    'on_click' => 'window.updateProductFromAiModal && window.updateProductFromAiModal.open(' . $productId . ')',
                    'sort_order' => 50,
                ];
            }
        } catch (\Exception $e) {
            // If getProduct() doesn't exist or fails, skip adding the button
        }

        return $result;
    }
}

