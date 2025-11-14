<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Squadkin\SquadexaAI\Service\SquadexaApiService;

class TokenStatus extends Field
{
    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param Context $context
     * @param SquadexaApiService $apiService
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        SquadexaApiService $apiService,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->apiService = $apiService;
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
     * Render API key status information
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $apiKey = $this->apiService->getApiKey();
        
        $html = '<div class="token-status-info" style="padding: 15px; background: #f8f9fa; border-radius: 5px; margin: 10px 0;">';
        
        if (!empty($apiKey)) {
            try {
                // Fetch API key metadata from the API
                $apiKeyMetadata = $this->apiService->getApiKeyMetadata();
                
                $html .= '<div style="color: green; font-weight: bold; margin-bottom: 10px;">✅ API Key Active</div>';
                
                // Display API key information
                if (isset($apiKeyMetadata['created_at'])) {
                    $formattedDate = $this->formatDateTime($apiKeyMetadata['created_at']);
                    $html .= '<div style="margin-bottom: 8px;"><strong>🕒 Created:</strong> ' . htmlspecialchars($formattedDate) . '</div>';
                }
                
                if (isset($apiKeyMetadata['last_used_at'])) {
                    $formattedDate = $this->formatDateTime($apiKeyMetadata['last_used_at']);
                    $html .= '<div style="margin-bottom: 8px;"><strong>🔄 Last Used:</strong> ' . htmlspecialchars($formattedDate) . '</div>';
                }
                
                $html .= '<div style="margin-bottom: 8px;"><strong>🔐 Status:</strong> <span style="color: green;">Active (Never Expires)</span></div>';
                
                // Display usage statistics if available
                if (isset($apiKeyMetadata['usage_count'])) {
                    $html .= '<div style="margin-bottom: 8px;"><strong>📊 Total API Calls:</strong> ' . number_format($apiKeyMetadata['usage_count']) . '</div>';
                }
                
                $html .= '<div style="color: #666; font-size: 12px; margin-top: 10px;">💡 This API key never expires and is used for all API requests.</div>';
                
            } catch (\Exception $e) {
                // If metadata fetch fails, show basic info
                $html .= '<div style="color: green; font-weight: bold; margin-bottom: 10px;">✅ API Key Configured</div>';
                $html .= '<div style="margin-bottom: 8px;"><strong>🔐 Status:</strong> <span style="color: green;">Active (Never Expires)</span></div>';
                $html .= '<div style="margin-bottom: 8px;"><strong>🔑 Key Length:</strong> ' . strlen($apiKey) . ' characters</div>';
                $html .= '<div style="color: #666; font-size: 12px; margin-top: 10px;">💡 This API key never expires and is used for all API requests.</div>';
            }
            
        } else {
            $html .= '<div style="color: red; font-weight: bold; margin-bottom: 10px;">❌ No API Key Generated</div>';
            $html .= '<div style="margin-top: 10px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 4px;">';
            $html .= '<strong style="color: #1976D2;">🚀 Quick Setup Guide:</strong>';
            $html .= '<ol style="margin: 10px 0 0 20px; color: #333;">';
            $html .= '<li style="margin-bottom: 8px;">Enter your <strong>Squadexa AI email</strong> and <strong>password</strong> in the fields above</li>';
            $html .= '<li style="margin-bottom: 8px;">Click the <strong>"Generate API Key"</strong> button</li>';
            $html .= '<li style="margin-bottom: 8px;">Your permanent API key will be <strong>generated automatically</strong></li>';
            $html .= '<li style="margin-bottom: 8px;">Start generating AI-powered product descriptions immediately!</li>';
            $html .= '</ol>';
            $html .= '<div style="margin-top: 10px; padding: 8px; background: #fff9e6; border-radius: 3px;">';
            $html .= '<small>💡 <strong>Don\'t have an account?</strong> Visit <a href="https://squadexa.ai/" target="_blank" style="color: #2196F3;">Squadexa AI Portal</a> to register first.</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Format ISO 8601 date/time string to readable format
     *
     * @param string $dateTimeString
     * @return string
     */
    private function formatDateTime(string $dateTimeString): string
    {
        try {
            // Parse ISO 8601 format (e.g., 2025-11-11T15:58:44.937254)
            // Remove microseconds if present
            $dateTimeString = preg_replace('/\.\d+/', '', $dateTimeString);
            
            // Create DateTime object from ISO 8601 string (assume UTC from API)
            $dateTime = new \DateTime($dateTimeString, new \DateTimeZone('UTC'));
            
            // Convert to store timezone and format using Magento's locale
            $formattedDate = $this->timezone->formatDateTime(
                $dateTime,
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::MEDIUM
            );
            
            return $formattedDate;
        } catch (\Exception $e) {
            // If formatting fails, try simple format
            try {
                $dateTime = new \DateTime($dateTimeString);
                return $dateTime->format('M d, Y h:i A');
            } catch (\Exception $e2) {
                // If all formatting fails, return original string
                return $dateTimeString;
            }
        }
    }
}
