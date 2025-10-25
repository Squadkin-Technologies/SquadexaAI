<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Psr\Log\LoggerInterface;

class AccountStatus extends Field
{
    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param SquadexaApiService $apiService
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        SquadexaApiService $apiService,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->apiService = $apiService;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Render account status information
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        try {
            $html = '<div id="squadexa-account-status">';
            
            // Check if any authentication is configured
            $accessToken = $this->apiService->getAccessToken();
            $apiKey = $this->apiService->getApiKey();
            
            if (empty($accessToken) && empty($apiKey)) {
                $html .= $this->renderError('No authentication configured. Please enter your username/password and click "Generate API Key" to authenticate.');
            } else {
                try {
                    // Test health check first
                    try {
                        $healthResult = $this->apiService->checkHealth();
                        $this->logger->info('SquadexaAI Health Check Success', ['result' => $healthResult]);
                    } catch (\Exception $healthError) {
                        $this->logger->error('SquadexaAI Health Check Failed', ['error' => $healthError->getMessage()]);
                        $html .= $this->renderError('API connection failed: ' . $healthError->getMessage());
                        $html .= '</div>';
                        return $html;
                    }
                    
                    // Try to get account information using API key
                    try {
                        $accountInfo = $this->apiService->getAccountInformation();
                        $html .= $this->renderAccountInfo($accountInfo);
                    } catch (\Exception $accountError) {
                        $this->logger->error('SquadexaAI Account Info Failed', ['error' => $accountError->getMessage()]);
                        
                        // Check if it's an authentication error
                        if (strpos($accountError->getMessage(), '401') !== false || 
                            strpos($accountError->getMessage(), 'Authentication required') !== false ||
                            strpos($accountError->getMessage(), 'expired') !== false) {
                            $html .= $this->renderError('Access token expired. Please click "Generate API Key" to refresh your authentication.');
                        } else {
                            $html .= $this->renderError('API key validation failed: ' . $accountError->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error('SquadexaAI Account Info Error', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $html .= $this->renderError($e->getMessage());
                }
            }

            $html .= '</div>';
            
            // Add JavaScript for auto-refresh
            $html .= $this->getRefreshScript();
            
            return $html;
        } catch (\Exception $e) {
            $this->logger->error('SquadexaAI AccountStatus Block Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a safe fallback HTML to prevent blank page
            return '<div id="squadexa-account-status">
                <div class="account-error">
                    <h3>⚠️ Configuration Error</h3>
                    <div class="error-message">
                        <p><strong>Error:</strong> Unable to load account status. Please check the logs for more details.</p>
                        <p>This is a fallback message to prevent the configuration page from being blank.</p>
                    </div>
                </div>
            </div>';
        }
    }

    /**
     * Render account information
     *
     * @param array $accountInfo
     * @return string
     */
    private function renderAccountInfo(array $accountInfo): string
    {
        if (!$accountInfo['api_key_valid']) {
            return $this->renderError('API key is invalid or not configured');
        }

        $html = '<div class="squadexa-account-dashboard">';
        
        // Usage & Overview Section
        $html .= $this->renderUsageOverview($accountInfo);
        
        // Account Status Section
        $html .= $this->renderAccountStatus($accountInfo);
        
        // Subscription Plan Section
        $html .= $this->renderSubscriptionPlan($accountInfo);
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render usage overview section
     *
     * @param array $accountInfo
     * @return string
     */
    private function renderUsageOverview(array $accountInfo): string
    {
        $usageStats = $accountInfo['usage_stats'] ?? [];
        $subscriptionPlan = $accountInfo['subscription_plan'] ?? [];
        
        $callsRemaining = $subscriptionPlan['calls_remaining'] ?? 0;
        $callsLimit = $subscriptionPlan['calls_limit'] ?? 5;
        $callsUsed = $callsLimit - $callsRemaining;
        $percentage = $callsLimit > 0 ? ($callsUsed / $callsLimit) * 100 : 0;
        
        $html = '<div class="usage-overview">';
        $html .= '<h3>📊 Usage & Overview</h3>';
        $html .= '<div class="usage-stats">';
        
        // API Usage Progress Bar
        $html .= '<div class="api-usage">';
        $html .= '<h4>⚡ API Usage</h4>';
        $html .= '<div class="progress-bar">';
        $html .= '<div class="progress-fill" style="width: ' . $percentage . '%"></div>';
        $html .= '</div>';
        $html .= '<p><strong>' . $callsRemaining . '/' . $callsLimit . '</strong> calls remaining</p>';
        $html .= '</div>';
        
        // Today's Activity
        $html .= '<div class="todays-activity">';
        $html .= '<h4>📈 Today\'s Activity</h4>';
        $html .= '<div class="activity-grid">';
        $html .= '<div class="activity-item">';
        $html .= '<span class="activity-count">' . ($usageStats['descriptions_today'] ?? 0) . '</span>';
        $html .= '<span class="activity-label">Product Descriptions Today</span>';
        $html .= '</div>';
        $html .= '<div class="activity-item">';
        $html .= '<span class="activity-count">' . ($usageStats['ai_humanizer_today'] ?? 0) . '</span>';
        $html .= '<span class="activity-label">AI Humanizer Today</span>';
        $html .= '</div>';
        $html .= '<div class="activity-item">';
        $html .= '<span class="activity-count">' . ($usageStats['ai_detector_today'] ?? 0) . '</span>';
        $html .= '<span class="activity-label">AI Detector Today</span>';
        $html .= '</div>';
        $html .= '<div class="activity-item">';
        $html .= '<span class="activity-count">' . ($usageStats['ai_tools_this_week'] ?? 0) . '</span>';
        $html .= '<span class="activity-label">AI Tools This Week</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render account status section
     *
     * @param array $accountInfo
     * @return string
     */
    private function renderAccountStatus(array $accountInfo): string
    {
        $userProfile = $accountInfo['user_profile'] ?? [];
        
        $html = '<div class="account-status">';
        $html .= '<h3>🔑 Account Status</h3>';
        $html .= '<div class="status-grid">';
        
        // API Key Status
        $html .= '<div class="status-item">';
        $html .= '<span class="status-icon">🔑</span>';
        $html .= '<span class="status-label">API Key Status</span>';
        $html .= '<span class="status-value status-active">Active Ready</span>';
        $html .= '</div>';
        
        // Current Plan
        $subscriptionPlan = $accountInfo['subscription_plan'] ?? [];
        $planName = $subscriptionPlan['name'] ?? 'FREE';
        $html .= '<div class="status-item">';
        $html .= '<span class="status-icon">📊</span>';
        $html .= '<span class="status-label">Current Plan</span>';
        $html .= '<span class="status-value">' . $planName . ' ' . ($subscriptionPlan['calls_limit'] ?? 5) . ' calls/month</span>';
        $html .= '</div>';
        
        // Calls Remaining
        $callsRemaining = $subscriptionPlan['calls_remaining'] ?? 0;
        $html .= '<div class="status-item">';
        $html .= '<span class="status-icon">⚡</span>';
        $html .= '<span class="status-label">Calls Remaining</span>';
        $html .= '<span class="status-value">' . $callsRemaining . ' Free Trial</span>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render subscription plan section
     *
     * @param array $accountInfo
     * @return string
     */
    private function renderSubscriptionPlan(array $accountInfo): string
    {
        $subscriptionPlan = $accountInfo['subscription_plan'] ?? [];
        $callsRemaining = $subscriptionPlan['calls_remaining'] ?? 0;
        $callsLimit = $subscriptionPlan['calls_limit'] ?? 5;
        $percentage = $callsLimit > 0 ? (($callsLimit - $callsRemaining) / $callsLimit) * 100 : 0;
        
        $html = '<div class="subscription-plan">';
        $html .= '<h3>📋 Usage Overview</h3>';
        $html .= '<div class="plan-details">';
        
        // Descriptions Generated Progress
        $html .= '<div class="descriptions-progress">';
        $html .= '<h4>📝 Descriptions Generated</h4>';
        $html .= '<div class="progress-bar">';
        $html .= '<div class="progress-fill" style="width: ' . $percentage . '%"></div>';
        $html .= '</div>';
        $html .= '<p><strong>' . ($callsLimit - $callsRemaining) . '/' . $callsLimit . '</strong> descriptions generated</p>';
        $html .= '</div>';
        
        // Free Trial Banner
        if ($callsRemaining > 0) {
            $html .= '<div class="trial-banner">';
            $html .= '<p>🎉 <strong>Free Trial</strong> You have ' . $callsRemaining . ' descriptions remaining in your free trial. <a href="#" target="_blank">Upgrade now</a> to get unlimited access.</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render error message
     *
     * @param string $message
     * @return string
     */
    private function renderError(string $message): string
    {
        try {
            $html = '<div class="account-error">';
            $html .= '<h3>❌ Account Status</h3>';
            $html .= '<div class="error-message">';
            $html .= '<p><strong>Error:</strong> ' . htmlspecialchars($message) . '</p>';
            $html .= '<p>Please check your API key configuration and try again.</p>';
            $html .= '<p><strong>Debug Info:</strong></p>';
            $html .= '<ul>';
            $html .= '<li>API Base URL: ' . htmlspecialchars($this->apiService->getApiBaseUrl()) . '</li>';
            $html .= '<li>Access Token Length: ' . strlen($this->apiService->getAccessToken()) . '</li>';
            $html .= '<li>Access Token Prefix: ' . htmlspecialchars(substr($this->apiService->getAccessToken(), 0, 10)) . '...</li>';
            $html .= '<li>API Key Length: ' . strlen($this->apiService->getApiKey()) . '</li>';
            $html .= '<li>API Key Prefix: ' . htmlspecialchars(substr($this->apiService->getApiKey(), 0, 10)) . '...</li>';
            $html .= '<li>Token Created: ' . htmlspecialchars($this->apiService->getTokenCreated()) . '</li>';
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        } catch (\Exception $e) {
            // Fallback error message if something goes wrong
            return '<div class="account-error">
                <h3>❌ Account Status</h3>
                <div class="error-message">
                    <p><strong>Error:</strong> ' . htmlspecialchars($message) . '</p>
                    <p>Please check your API key configuration and try again.</p>
                </div>
            </div>';
        }
    }

    /**
     * Get refresh script for auto-updating account status
     *
     * @return string
     */
    private function getRefreshScript(): string
    {
        return '
        <script>
        require(["jquery"], function($) {
            // Auto-refresh account status every 30 seconds
            setInterval(function() {
                // Trigger configuration save to refresh the block
                if ($("#squadexa-account-status").length) {
                    // You can add AJAX call here to refresh without page reload
                    console.log("Account status refresh triggered");
                }
            }, 30000);
        });
        </script>
        <style>
        .squadexa-account-dashboard {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 10px 0;
        }
        .usage-overview, .account-status, .subscription-plan {
            margin-bottom: 20px;
        }
        .usage-overview h3, .account-status h3, .subscription-plan h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            transition: width 0.3s ease;
        }
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .activity-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .activity-count {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .activity-label {
            display: block;
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .status-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-icon {
            font-size: 20px;
        }
        .status-label {
            flex: 1;
            font-weight: 500;
        }
        .status-value {
            font-weight: bold;
        }
        .status-active {
            color: #28a745;
        }
        .trial-banner {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        .trial-banner a {
            color: #007bff;
            text-decoration: none;
        }
        .account-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 15px;
        }
        .error-message {
            color: #721c24;
        }
        </style>';
    }
}
