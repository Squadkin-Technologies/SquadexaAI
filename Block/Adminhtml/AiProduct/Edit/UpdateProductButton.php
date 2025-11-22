<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\AiProduct\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Update Product in Magento Button
 */
class UpdateProductButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        $aiproductId = $this->getAiProductId();
        
        // Only show button if product is already created in Magento
        if ($aiproductId && $this->isCreatedInMagento()) {
            $magentoProductId = $this->getMagentoProductId();
            if ($magentoProductId) {
                // Use the same redirect approach as create - redirect to product edit page with ai_data parameter
                // Build absolute URL with secret key using getUrl() from GenericButton
                $editUrl = $this->getUrl(
                    'catalog/product/edit',
                    ['id' => $magentoProductId]
                );
                
                // Add ai_data as query parameter
                $separator = strpos($editUrl, '?') !== false ? '&' : '?';
                $redirectUrl = $editUrl . $separator . 'ai_data=' . urlencode((string)$aiproductId);
                
                $data = [
                    'label' => __('Update Product in Magento'),
                    'class' => 'primary',
                    'on_click' => sprintf(
                        "if (confirm('%s')) { window.location.href = '%s'; }",
                        __('You will be redirected to the product edit page where you can review and apply the latest AI-generated data. Continue?'),
                        $redirectUrl
                    ),
                    'sort_order' => 30,
                ];
            }
        }
        return $data;
    }

    /**
     * Get Magento Product ID
     *
     * @return int|null
     */
    private function getMagentoProductId()
    {
        try {
            $aiproductId = $this->getAiProductId();
            if ($aiproductId) {
                $aiProduct = $this->aiProductRepository->get($aiproductId);
                return (int)$aiProduct->getMagentoProductId() ?: null;
            }
        } catch (\Exception $e) {
            // Product not found, return null
        }
        return null;
    }
    
    /**
     * Get AI Product
     *
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface|null
     */
    private function getAiProduct()
    {
        try {
            $aiproductId = $this->getAiProductId();
            if ($aiproductId) {
                return $this->aiProductRepository->get($aiproductId);
            }
        } catch (\Exception $e) {
            // Product not found, return null
        }
        return null;
    }
}

