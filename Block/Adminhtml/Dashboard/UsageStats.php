<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Squadkin\SquadexaAI\Service\SquadexaApiService;

class UsageStats extends Template
{
    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Template\Context $context
     * @param SquadexaApiService $apiService
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SquadexaApiService $apiService,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->apiService = $apiService;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get usage statistics
     *
     * @return array
     */
    public function getUsageStats(): array
    {
        try {
            return $this->apiService->getUsageStats();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get account information
     *
     * @return array
     */
    public function getAccountInfo(): array
    {
        try {
            return $this->apiService->getAccountInformation();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue('squadexaiproductcreator/general/is_enable');
    }

    /**
     * Check if API key is configured
     *
     * @return bool
     */
    public function isAuthenticationConfigured(): bool
    {
        $accessToken = $this->scopeConfig->getValue('squadexaiproductcreator/authentication/access_token');
        $apiKey = $this->scopeConfig->getValue('squadexaiproductcreator/authentication/api_key');
        return !empty($accessToken) || !empty($apiKey);
    }

    /**
     * Get redirect URL for account creation
     *
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->apiService->getRedirectUrl();
    }

    /**
     * Get formatted usage data for display
     *
     * @return array
     */
    public function getFormattedUsageData(): array
    {
        $accountInfo = $this->getAccountInfo();
        
        if (empty($accountInfo) || !$accountInfo['api_key_valid']) {
            return [
                'error' => true,
                'message' => 'Authentication not configured or invalid'
            ];
        }

        $usageStats = $accountInfo['usage_stats'] ?? [];
        $subscriptionPlan = $accountInfo['subscription_plan'] ?? [];
        
        return [
            'error' => false,
            'calls_remaining' => $subscriptionPlan['calls_remaining'] ?? 0,
            'calls_limit' => $subscriptionPlan['calls_limit'] ?? 5,
            'calls_used' => ($subscriptionPlan['calls_limit'] ?? 5) - ($subscriptionPlan['calls_remaining'] ?? 0),
            'plan_name' => $subscriptionPlan['name'] ?? 'FREE',
            'descriptions_today' => $usageStats['descriptions_today'] ?? 0,
            'ai_humanizer_today' => $usageStats['ai_humanizer_today'] ?? 0,
            'ai_detector_today' => $usageStats['ai_detector_today'] ?? 0,
            'ai_tools_this_week' => $usageStats['ai_tools_this_week'] ?? 0,
            'total_today' => $usageStats['total_today'] ?? 0
        ];
    }

    /**
     * Get percentage of usage
     *
     * @return float
     */
    public function getUsagePercentage(): float
    {
        $data = $this->getFormattedUsageData();
        
        if ($data['error'] || $data['calls_limit'] == 0) {
            return 0;
        }
        
        return ($data['calls_used'] / $data['calls_limit']) * 100;
    }

    /**
     * Get progress bar color based on usage
     *
     * @return string
     */
    public function getProgressBarColor(): string
    {
        $percentage = $this->getUsagePercentage();
        
        if ($percentage >= 90) {
            return '#dc3545'; // Red
        } elseif ($percentage >= 70) {
            return '#ffc107'; // Yellow
        } else {
            return '#28a745'; // Green
        }
    }
}
