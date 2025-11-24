<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Psr\Log\LoggerInterface;

class Dashboard extends Template
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
     * Get API key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiService->getApiKey();
    }

    /**
     * Get dashboard data
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        try {
            $data = [
                'has_data' => false,
                'user_profile' => null,
                'usage_stats' => null,
                'subscription' => null,
                'recent_activities' => [],
            ];
            
            // Fetch user profile
            try {
                $userProfile = $this->apiService->getUserProfile();
                $this->logger->info('SquadexaAI Dashboard - User Profile Response:', ['data' => $userProfile]);
                
                if (!empty($userProfile)) {
                    $data['user_profile'] = $userProfile;
                    $data['has_data'] = true;
                }
            } catch (\Exception $e) {
                $this->logger->error('User profile not available: ' . $e->getMessage());
            }
            
            // Fetch usage statistics
            try {
                $usageStats = $this->apiService->getUsageStats();
                $this->logger->info('SquadexaAI Dashboard - Usage Stats Response:', ['data' => $usageStats]);
                
                if (!empty($usageStats)) {
                    $data['usage_stats'] = $usageStats;
                    $data['has_data'] = true;
                }
            } catch (\Exception $e) {
                $this->logger->error('Usage stats not available: ' . $e->getMessage());
            }
            
            // Fetch subscription info
            try {
                $subscription = $this->apiService->getCurrentSubscription();
                $this->logger->info('SquadexaAI Dashboard - Subscription Response:', ['data' => $subscription]);
                
                if (!empty($subscription)) {
                    $data['subscription'] = $subscription;
                    $data['has_data'] = true;
                }
            } catch (\Exception $e) {
                $this->logger->error('Subscription info not available: ' . $e->getMessage());
            }
            
            // Fetch recent activities (all activities, pagination handled in template)
            try {
                $usageHistory = $this->apiService->getUsageHistory();
                $this->logger->info('SquadexaAI Dashboard - Usage History Response:', ['data' => $usageHistory]);
                
                if (!empty($usageHistory['history'])) {
                    // Get all activities, pagination will be handled in template
                    $data['recent_activities'] = $usageHistory['history'];
                    $data['has_data'] = true;
                }
            } catch (\Exception $e) {
                $this->logger->error('Recent activities not available: ' . $e->getMessage());
            }
            
            return $data;
            
        } catch (\Exception $e) {
            $this->logger->error('Dashboard data fetch error: ' . $e->getMessage());
            return [
                'has_data' => false,
                'user_profile' => null,
                'usage_stats' => null,
                'subscription' => null,
                'recent_activities' => [],
            ];
        }
    }
    
    /**
     * Check if section should be displayed
     *
     * @param array $data
     * @param string $section
     * @return bool
     */
    public function shouldShowSection(array $data, string $section): bool
    {
        switch ($section) {
            case 'plan_status':
                return !empty($data['subscription']);
            case 'usage_cards':
                return !empty($data['usage_stats']);
            case 'monthly_progress':
                return !empty($data['usage_stats']);
            case 'recent_activity':
                return !empty($data['recent_activities']);
            case 'account_status':
                return !empty($data['user_profile']);
            default:
                return false;
        }
    }
}
