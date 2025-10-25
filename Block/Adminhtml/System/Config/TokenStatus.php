<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Squadkin\SquadexaAI\Service\SquadexaApiService;

class TokenStatus extends Field
{
    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @param Context $context
     * @param SquadexaApiService $apiService
     * @param array $data
     */
    public function __construct(
        Context $context,
        SquadexaApiService $apiService,
        array $data = []
    ) {
        $this->apiService = $apiService;
        parent::__construct($context, $data);
    }

    /**
     * Render token status information
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $accessToken = $this->apiService->getAccessToken();
        $apiKey = $this->apiService->getApiKey();
        $tokenCreated = $this->apiService->getTokenCreated();
        
        $html = '<div class="token-status-info" style="padding: 15px; background: #f8f9fa; border-radius: 5px; margin: 10px 0;">';
        
        if (!empty($accessToken) && !empty($apiKey)) {
            $html .= '<div style="color: green; font-weight: bold; margin-bottom: 10px;">✅ Authentication Active</div>';
            
            if (!empty($tokenCreated)) {
                $html .= '<div style="margin-bottom: 8px;"><strong>🕒 Token Created:</strong> ' . htmlspecialchars($tokenCreated) . '</div>';
            } else {
                $html .= '<div style="margin-bottom: 8px;"><strong>🕒 Token Created:</strong> <span style="color: #666;">Not recorded</span></div>';
            }
            
            $html .= '<div style="margin-bottom: 8px;"><strong>⏰ Access Token:</strong> <span style="color: orange;">Active (30 min expiry)</span></div>';
            $html .= '<div style="margin-bottom: 8px;"><strong>🔐 API Key:</strong> <span style="color: green;">Active (Permanent)</span></div>';
            
            // Calculate token age
            if (!empty($tokenCreated)) {
                try {
                    $createdTime = strtotime($tokenCreated);
                    $currentTime = time();
                    $ageMinutes = floor(($currentTime - $createdTime) / 60);
                    $remainingMinutes = 30 - $ageMinutes;
                    
                    if ($remainingMinutes > 0) {
                        $html .= '<div style="margin-bottom: 8px;"><strong>⏳ Token Age:</strong> ' . $ageMinutes . ' minutes (expires in ' . $remainingMinutes . ' minutes)</div>';
                    } else {
                        $html .= '<div style="margin-bottom: 8px;"><strong>⚠️ Token Age:</strong> ' . $ageMinutes . ' minutes (expired - will auto-refresh)</div>';
                    }
                } catch (\Exception $e) {
                    $html .= '<div style="margin-bottom: 8px;"><strong>⏳ Token Age:</strong> Unable to calculate</div>';
                }
            }
            
        } elseif (!empty($accessToken)) {
            $html .= '<div style="color: orange; font-weight: bold; margin-bottom: 10px;">⚠️ Partial Authentication</div>';
            $html .= '<div style="margin-bottom: 8px;"><strong>⏰ Access Token:</strong> <span style="color: orange;">Active (30 min expiry)</span></div>';
            $html .= '<div style="margin-bottom: 8px;"><strong>🔐 API Key:</strong> <span style="color: red;">Not Generated</span></div>';
            $html .= '<div style="color: #666; font-size: 12px;">Click "Generate API Key" to complete authentication</div>';
            
        } else {
            $html .= '<div style="color: red; font-weight: bold; margin-bottom: 10px;">❌ No Authentication</div>';
            $html .= '<div style="color: #666; font-size: 12px;">Enter username/password and click "Generate API Key" to authenticate</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
