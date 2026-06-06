<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * phpcs:ignoreFile Generic.Files.LineLength,Magento2.Security.InsecureFunction
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
                // Fetch API key metadata and account info
                $apiKeyMetadata = $this->apiService->getApiKeyMetadata();
                $accountInfo = $this->apiService->getAccountInformation();

                $html .= '<div style="color: #28a745; font-weight: bold; margin-bottom: 15px; font-size: 15px;">✅ API Key Active & Connected</div>';

                // API Key Info Card
                $html .= '<div style="background: white; padding: 12px; border-radius: 4px; margin-bottom: 12px; border-left: 3px solid #4f46e5;">';
                $html .= '<div style="margin-bottom: 8px;"><strong>🔐 API Key:</strong> ';
                if (isset($apiKeyMetadata['api_key_masked'])) {
                    $html .= htmlspecialchars($apiKeyMetadata['api_key_masked']);
                } elseif (isset($apiKeyMetadata['api_key_last8'])) {
                    $html .= 'pk_...' . htmlspecialchars($apiKeyMetadata['api_key_last8']);
                } else {
                    $html .= 'pk_...' . substr($apiKey, -4);
                }
                $html .= '</div>';

                if (isset($apiKeyMetadata['created_at'])) {
                    $formattedDate = $this->formatDateTime($apiKeyMetadata['created_at']);
                    $html .= '<div style="margin-bottom: 8px; font-size: 12px; color: #666;"><strong>Created:</strong> ';
                    $html .= htmlspecialchars($formattedDate) . '</div>';
                }

                if (isset($apiKeyMetadata['last_used_at'])) {
                    $formattedDate = $this->formatDateTime($apiKeyMetadata['last_used_at']);
                    $html .= '<div style="margin-bottom: 8px; font-size: 12px; color: #666;"><strong>Last Used:</strong> ';
                    $html .= htmlspecialchars($formattedDate) . '</div>';
                }

                $html .= '<div style="font-size: 12px; color: #666;"><strong>Status:</strong> <span style="color: #28a745;">Active (Never Expires)</span></div>';
                $html .= '</div>';

                // Credit Wallet Info
                if (isset($accountInfo['subscription_plan'])) {
                    $plan = $accountInfo['subscription_plan'];
                    $creditsRemaining = isset($plan['credits_remaining']) ? (int)$plan['credits_remaining'] : 0;
                    $creditsUsed = isset($plan['credits_used']) ? (int)$plan['credits_used'] : 0;
                    $creditsTotal = $creditsRemaining + $creditsUsed;

                    $html .= '<div style="background: white; padding: 12px; border-radius: 4px; border-left: 3px solid #10b981;">';
                    $html .= '<div style="margin-bottom: 10px;"><strong>💳 Credit Wallet</strong></div>';

                    if ($creditsTotal > 0) {
                        $percentage = ($creditsUsed / $creditsTotal) * 100;
                        $progressColor = $percentage >= 90 ? '#ef4444' : ($percentage >= 70 ? '#f59e0b' : '#10b981');

                        $html .= '<div style="margin-bottom: 8px;">';
                        $html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 12px;">';
                        $html .= '<span>Credits Used</span>';
                        $html .= '<span style="color: #666;">' . number_format($creditsUsed) . ' / ' . number_format($creditsTotal) . '</span>';
                        $html .= '</div>';
                        $html .= '<div style="width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">';
                        $html .= '<div style="height: 100%; width: ' . min($percentage, 100) . '%; background: ' . $progressColor . '; transition: width 0.3s;"></div>';
                        $html .= '</div>';
                        $html .= '</div>';
                    }

                    $html .= '<div style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 3px; font-size: 12px;">';
                    $html .= '<strong>💰 Credits Remaining:</strong> ' . number_format($creditsRemaining);

                    if ($creditsRemaining <= 5 && $creditsRemaining > 0) {
                        $html .= '<div style="margin-top: 8px; color: #d97706;">';
                        $html .= '⚠️ <strong>Low on credits!</strong> ';
                        $topupLink = isset($plan['cta_link']) ? $plan['cta_link'] : 'https://www.squadexa.ai/account#credits';
                        $html .= '<a href="' . htmlspecialchars($topupLink) . '" target="_blank" style="color: #d97706; font-weight: bold; text-decoration: none;">';
                        $html .= 'Top Up Now →</a>';
                        $html .= '</div>';
                    } elseif ($creditsRemaining <= 0) {
                        $html .= '<div style="margin-top: 8px; color: #dc2626;">';
                        $html .= '❌ <strong>No credits available.</strong> ';
                        $topupLink = isset($plan['cta_link']) ? $plan['cta_link'] : 'https://www.squadexa.ai/account#credits';
                        $html .= '<a href="' . htmlspecialchars($topupLink) . '" target="_blank" style="color: #dc2626; font-weight: bold; text-decoration: none;">';
                        $html .= 'Add Credits →</a>';
                        $html .= '</div>';
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                }

            } catch (\Exception $e) {
                // If API calls fail, show basic info
                $html .= '<div style="background: white; padding: 12px; border-radius: 4px; border-left: 3px solid #4f46e5;">';
                $html .= '<div style="margin-bottom: 8px;"><strong>🔐 API Key:</strong> pk_...' . substr($apiKey, -4) . '</div>';
                $html .= '<div style="font-size: 12px; color: #666;"><strong>Status:</strong> <span style="color: #28a745;">Active</span></div>';
                $html .= '<div style="margin-top: 8px; padding: 8px; background: #f0f9ff; border-radius: 3px; font-size: 12px; color: #666;">';
                $html .= 'Unable to fetch credit balance. Please try refreshing the page.';
                $html .= '</div>';
                $html .= '</div>';
            }

        } else {
            $html .= '<div style="color: #dc2626; font-weight: bold; margin-bottom: 10px;">❌ No API Key Generated</div>';
            $html .= '<div style="margin-top: 10px; padding: 15px; background: #e7f3ff; border-left: 4px solid #4f46e5; border-radius: 4px;">';
            $html .= '<strong style="color: #1976d2;">🚀 Quick Setup Guide:</strong>';
            $html .= '<ol style="margin: 10px 0 0 20px; color: #333; padding-left: 0;">';
            $html .= '<li style="margin-bottom: 8px;">Create a free account at <a href="https://www.squadexa.ai/" target="_blank" style="color: #1976d2;">Squadexa AI</a></li>';
            $html .= '<li style="margin-bottom: 8px;">Enter your email and password in the fields above</li>';
            $html .= '<li style="margin-bottom: 8px;">Click the <strong>"Generate API Key"</strong> button</li>';
            $html .= '<li style="margin-bottom: 8px;">Your permanent API key will be generated automatically</li>';
            $html .= '</ol>';
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
