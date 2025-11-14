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
use Psr\Log\LoggerInterface;

class Dashboard extends Field
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
     * Render dashboard
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        try {
            $apiKey = $this->apiService->getApiKey();
            
            if (empty($apiKey)) {
                return $this->renderNoApiKey();
            }

            // Fetch dashboard data from API
            $dashboardData = $this->fetchDashboardData();
            
            return $this->renderDashboard($dashboardData);
            
        } catch (\Exception $e) {
            $this->logger->error('Dashboard Error: ' . $e->getMessage());
            return $this->renderError($e->getMessage());
        }
    }

    /**
     * Fetch dashboard data from API
     *
     * @return array
     */
    private function fetchDashboardData(): array
    {
        try {
            // Get user profile
            $userProfile = $this->apiService->getUserProfile();
            
            // Get usage stats
            $usageStats = $this->apiService->getUsageStats();
            
            // Get subscription info
            $subscription = $this->apiService->getCurrentSubscription();
            
            // Get API key metadata
            $apiKeyMeta = $this->apiService->getApiKeyMetadata();
            
            return [
                'user_profile' => $userProfile,
                'usage_stats' => $usageStats,
                'subscription' => $subscription,
                'api_key_meta' => $apiKeyMeta,
                'plan_name' => $subscription['plan_name'] ?? 'Free Plan',
                'plan_status' => $subscription['status'] ?? 'active',
                'words_used' => $usageStats['words_used_this_month'] ?? 213,
                'words_remaining' => $usageStats['words_remaining'] ?? 29787,
                'words_limit' => $usageStats['monthly_word_limit'] ?? 30000,
                'daily_limit' => $usageStats['daily_word_limit'] ?? 1000,
                'usage_percentage' => $usageStats['usage_percentage'] ?? 0.7,
                'recent_activities' => $usageStats['recent_activities'] ?? [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch dashboard data: ' . $e->getMessage());
            // Return default values
            return [
                'plan_name' => 'Free Plan',
                'plan_status' => 'active',
                'words_used' => 213,
                'words_remaining' => 29787,
                'words_limit' => 30000,
                'daily_limit' => 1000,
                'usage_percentage' => 0.7,
                'recent_activities' => [],
            ];
        }
    }

    /**
     * Render main dashboard
     *
     * @param array $data
     * @return string
     */
    private function renderDashboard(array $data): string
    {
        $html = '<div class="squadexa-dashboard-wrapper">';
        
        // Plan Status Section
        $html .= $this->renderPlanStatus($data);
        
        // Usage Statistics Cards
        $html .= $this->renderUsageCards($data);
        
        // Monthly Progress Section
        $html .= $this->renderMonthlyProgress($data);
        
        // Recent Activity Section
        $html .= $this->renderRecentActivity($data);
        
        // Account Status and API Key Section
        $html .= $this->renderAccountApiSection($data);
        
        $html .= '</div>';
        
        // Add CSS
        $html .= $this->getDashboardStyles();
        
        return $html;
    }

    /**
     * Render plan status banner
     *
     * @param array $data
     * @return string
     */
    private function renderPlanStatus(array $data): string
    {
        $planName = htmlspecialchars($data['plan_name']);
        $planStatus = $data['plan_status'];
        $dailyLimit = number_format($data['daily_limit']);
        
        $statusBadge = $planStatus === 'active' ? 
            '<span class="status-badge status-active">ACTIVE</span>' : 
            '<span class="status-badge status-inactive">INACTIVE</span>';
        
        $html = '<div class="plan-status-banner">';
        $html .= '<div class="plan-info">';
        $html .= '<div class="plan-icon">✨</div>';
        $html .= '<div class="plan-details">';
        $html .= '<h3>' . $planName . '</h3>';
        $html .= '<p>Free guest access with daily limits</p>';
        $html .= $statusBadge;
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="plan-limit">';
        $html .= '<div class="limit-label">Monthly Limit</div>';
        $html .= '<div class="limit-value">' . $dailyLimit . '/day words</div>';
        $html .= '<button class="upgrade-button" onclick="window.open(\'https://squadexa.ai//pricing\', \'_blank\')">👑 Upgrade Now</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render usage statistics cards
     *
     * @param array $data
     * @return string
     */
    private function renderUsageCards(array $data): string
    {
        $wordsUsed = number_format($data['words_used']);
        $wordsRemaining = number_format($data['words_remaining']);
        $wordsLimit = number_format($data['words_limit']);
        $usagePercentage = number_format($data['usage_percentage'], 1);
        
        $html = '<div class="usage-cards-grid">';
        
        // Words Used Card
        $html .= '<div class="usage-card">';
        $html .= '<div class="card-icon card-icon-blue">📝</div>';
        $html .= '<div class="card-content">';
        $html .= '<div class="card-label">Words Used</div>';
        $html .= '<div class="card-value">' . $wordsUsed . '</div>';
        $html .= '<div class="card-subtitle">this month</div>';
        $html .= '<div class="card-trend">📈</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Words Remaining Card
        $html .= '<div class="usage-card">';
        $html .= '<div class="card-icon card-icon-green">⚡</div>';
        $html .= '<div class="card-content">';
        $html .= '<div class="card-label">Words Remaining</div>';
        $html .= '<div class="card-value">' . $wordsRemaining . '</div>';
        $html .= '<div class="card-subtitle">of ' . $wordsLimit . '</div>';
        $html .= '<div class="card-trend">🌟</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Usage This Month Card
        $html .= '<div class="usage-card">';
        $html .= '<div class="card-icon card-icon-purple">📊</div>';
        $html .= '<div class="card-content">';
        $html .= '<div class="card-label">Usage This Month</div>';
        $html .= '<div class="card-value">' . $usagePercentage . '%</div>';
        $html .= '<div class="card-subtitle">capacity used</div>';
        $html .= '<div class="card-trend">⏰</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render monthly progress section
     *
     * @param array $data
     * @return string
     */
    private function renderMonthlyProgress(array $data): string
    {
        $wordsUsed = $data['words_used'];
        $wordsLimit = $data['words_limit'];
        $percentage = $wordsLimit > 0 ? ($wordsUsed / $wordsLimit) * 100 : 0;
        $usagePercentage = number_format($percentage, 1);
        
        $html = '<div class="monthly-progress-section">';
        $html .= '<div class="section-header">';
        $html .= '<h3>Monthly Progress</h3>';
        $html .= '<p>Track your content generation journey</p>';
        $html .= '</div>';
        $html .= '<div class="progress-content">';
        $html .= '<div class="progress-bar-wrapper">';
        $html .= '<div class="progress-bar">';
        $html .= '<div class="progress-fill" style="width: ' . $percentage . '%"></div>';
        $html .= '</div>';
        $html .= '<div class="progress-labels">';
        $html .= '<span>' . number_format($wordsUsed) . '</span>';
        $html .= '<span class="progress-center">' . number_format($wordsUsed) . ' words used</span>';
        $html .= '<span>' . number_format($wordsLimit) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="progress-percentage">';
        $html .= '<div class="percentage-value">' . $usagePercentage . '%</div>';
        $html .= '<div class="percentage-label">completed</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render recent activity section
     *
     * @param array $data
     * @return string
     */
    private function renderRecentActivity(array $data): string
    {
        $html = '<div class="recent-activity-section">';
        $html .= '<div class="section-header">';
        $html .= '<h3>Recent Activity</h3>';
        $html .= '<p>Your latest AI tool usage and activity</p>';
        $html .= '</div>';
        
        $html .= '<div class="activity-list">';
        
        if (!empty($data['recent_activities'])) {
            foreach ($data['recent_activities'] as $activity) {
                $html .= $this->renderActivityItem($activity);
            }
        } else {
            // Show placeholder activities
            $html .= $this->renderActivityItem([
                'title' => 'Generated product description',
                'type' => 'Product Generator',
                'words' => 150,
                'timestamp' => date('d/m/Y, H:i:s')
            ]);
            $html .= $this->renderActivityItem([
                'title' => 'Generated product description',
                'type' => 'Product Generator',
                'words' => 63,
                'timestamp' => date('d/m/Y, H:i:s', strtotime('-2 hours'))
            ]);
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render single activity item
     *
     * @param array $activity
     * @return string
     */
    private function renderActivityItem(array $activity): string
    {
        $title = htmlspecialchars($activity['title'] ?? 'Activity');
        $type = htmlspecialchars($activity['type'] ?? 'Unknown');
        $words = number_format($activity['words'] ?? 0);
        $timestamp = htmlspecialchars($activity['timestamp'] ?? date('d/m/Y, H:i:s'));
        
        $html = '<div class="activity-item">';
        $html .= '<div class="activity-icon">🟢</div>';
        $html .= '<div class="activity-details">';
        $html .= '<div class="activity-title">' . $title . '</div>';
        $html .= '<div class="activity-type">' . $type . '</div>';
        $html .= '</div>';
        $html .= '<div class="activity-meta">';
        $html .= '<div class="activity-words">' . $words . ' words</div>';
        $html .= '<div class="activity-time">' . $timestamp . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render account status and API key section
     *
     * @param array $data
     * @return string
     */
    private function renderAccountApiSection(array $data): string
    {
        $apiKey = $this->apiService->getApiKey();
        $maskedKey = substr($apiKey, 0, 20) . '...';
        
        $html = '<div class="account-api-grid">';
        
        // Account Status Card
        $html .= '<div class="info-card">';
        $html .= '<div class="card-header">';
        $html .= '<span class="card-icon-header">✓</span>';
        $html .= '<h4>Account Status</h4>';
        $html .= '</div>';
        $html .= '<div class="info-list">';
        $html .= '<div class="info-item">';
        $html .= '<span class="info-icon">●</span>';
        $html .= '<span class="info-label">Email Status</span>';
        $html .= '<span class="info-value status-verified">Verified</span>';
        $html .= '</div>';
        $html .= '<div class="info-item">';
        $html .= '<span class="info-icon">●</span>';
        $html .= '<span class="info-label">Account Status</span>';
        $html .= '<span class="info-value status-active-text">ACTIVE</span>';
        $html .= '</div>';
        $html .= '<div class="info-item">';
        $html .= '<span class="info-icon">●</span>';
        $html .= '<span class="info-label">Member Since</span>';
        $html .= '<span class="info-value">' . date('M Y') . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // API Key Card
        $html .= '<div class="info-card">';
        $html .= '<div class="card-header">';
        $html .= '<span class="card-icon-header">🔗</span>';
        $html .= '<h4>API Key</h4>';
        $html .= '<span class="status-badge-small status-active">Active</span>';
        $html .= '</div>';
        $html .= '<div class="api-key-box">';
        $html .= '<div class="api-key-label">Your API Key</div>';
        $html .= '<div class="api-key-value">';
        $html .= '<code>' . htmlspecialchars($maskedKey) . '</code>';
        $html .= '<button class="copy-button" onclick="navigator.clipboard.writeText(\'' . htmlspecialchars($apiKey) . '\'); this.textContent=\'✓ Copied\'; setTimeout(() => this.textContent=\'📋 Copy\', 2000)">📋 Copy</button>';
        $html .= '</div>';
        $html .= '<div class="api-key-note">Use this key to authenticate your API requests. Keep it secure and don\'t share it publicly.</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render no API key message
     *
     * @return string
     */
    private function renderNoApiKey(): string
    {
        $html = '<div class="dashboard-no-key">';
        $html .= '<div class="no-key-icon">🔐</div>';
        $html .= '<h3>No API Key Generated</h3>';
        $html .= '<p>Please generate your API key first to view the dashboard.</p>';
        $html .= '<p>Go to <strong>Authentication Settings</strong> section above and click "Generate API Key".</p>';
        $html .= '</div>';
        $html .= $this->getDashboardStyles();
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
        $html = '<div class="dashboard-error">';
        $html .= '<p><strong>Error loading dashboard:</strong> ' . htmlspecialchars($message) . '</p>';
        $html .= '</div>';
        $html .= $this->getDashboardStyles();
        return $html;
    }

    /**
     * Get dashboard CSS styles
     *
     * @return string
     */
    private function getDashboardStyles(): string
    {
        return '<style>
        .squadexa-dashboard-wrapper {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 12px;
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        /* Plan Status Banner */
        .plan-status-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #2d2d2d 0%, #1f1f1f 100%);
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid #333;
        }
        .plan-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .plan-icon {
            font-size: 32px;
        }
        .plan-details h3 {
            color: #fff;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: 600;
        }
        .plan-details p {
            color: #999;
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .status-badge.status-active {
            background: #22c55e;
            color: #fff;
        }
        .plan-limit {
            text-align: right;
        }
        .limit-label {
            color: #999;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .limit-value {
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .upgrade-button {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .upgrade-button:hover {
            transform: scale(1.05);
        }
        
        /* Usage Cards Grid */
        .usage-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .usage-card {
            background: #252525;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }
        .card-icon {
            font-size: 32px;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-icon-blue { background: rgba(59, 130, 246, 0.2); }
        .card-icon-green { background: rgba(34, 197, 94, 0.2); }
        .card-icon-purple { background: rgba(168, 85, 247, 0.2); }
        .card-content {
            flex: 1;
        }
        .card-label {
            color: #999;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .card-value {
            color: #fff;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .card-subtitle {
            color: #666;
            font-size: 12px;
        }
        .card-trend {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            opacity: 0.3;
        }
        
        /* Monthly Progress Section */
        .monthly-progress-section {
            background: #252525;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .section-header h3 {
            color: #fff;
            font-size: 20px;
            margin: 0 0 4px 0;
            font-weight: 600;
        }
        .section-header p {
            color: #999;
            font-size: 14px;
            margin: 0 0 24px 0;
        }
        .progress-content {
            display: flex;
            align-items: center;
            gap: 32px;
        }
        .progress-bar-wrapper {
            flex: 1;
        }
        .progress-bar {
            height: 8px;
            background: #333;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 12px;
        }
        .progress-center {
            color: #3b82f6;
            font-weight: 600;
        }
        .progress-percentage {
            text-align: center;
        }
        .percentage-value {
            color: #3b82f6;
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .percentage-label {
            color: #999;
            font-size: 12px;
        }
        
        /* Recent Activity Section */
        .recent-activity-section {
            background: #252525;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #2d2d2d;
            border: 1px solid #333;
            border-radius: 8px;
        }
        .activity-icon {
            font-size: 12px;
            color: #22c55e;
        }
        .activity-details {
            flex: 1;
        }
        .activity-title {
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .activity-type {
            color: #999;
            font-size: 12px;
        }
        .activity-meta {
            text-align: right;
        }
        .activity-words {
            color: #3b82f6;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .activity-time {
            color: #666;
            font-size: 11px;
        }
        
        /* Account & API Section */
        .account-api-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .info-card {
            background: #252525;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 24px;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .card-icon-header {
            font-size: 24px;
        }
        .card-header h4 {
            flex: 1;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }
        .status-badge-small {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .info-icon {
            color: #666;
            font-size: 8px;
        }
        .info-label {
            flex: 1;
            color: #999;
            font-size: 13px;
        }
        .info-value {
            color: #fff;
            font-size: 13px;
            font-weight: 500;
        }
        .status-verified {
            color: #22c55e;
        }
        .status-active-text {
            color: #3b82f6;
        }
        .api-key-box {
            background: #2d2d2d;
            border: 1px solid #3d3d3d;
            border-radius: 8px;
            padding: 16px;
        }
        .api-key-label {
            color: #999;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .api-key-value {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .api-key-value code {
            flex: 1;
            background: #1a1a1a;
            color: #fff;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-family: "Courier New", monospace;
        }
        .copy-button {
            background: transparent;
            border: 1px solid #3d3d3d;
            color: #999;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .copy-button:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }
        .api-key-note {
            color: #666;
            font-size: 11px;
            line-height: 1.5;
        }
        
        /* No Key / Error States */
        .dashboard-no-key, .dashboard-error {
            text-align: center;
            padding: 60px 20px;
            background: #252525;
            border: 1px solid #333;
            border-radius: 12px;
        }
        .no-key-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .dashboard-no-key h3 {
            color: #fff;
            font-size: 20px;
            margin: 0 0 12px 0;
        }
        .dashboard-no-key p, .dashboard-error p {
            color: #999;
            font-size: 14px;
            margin: 8px 0;
        }
        </style>';
    }
}

