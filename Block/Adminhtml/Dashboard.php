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
    public function getDashboardData(): array // @codingStandardsIgnoreLine
    {
        try {
            $data = [
                'has_data' => false,
                'user_profile' => null,
                'usage_stats' => null,
                'subscription' => null,
                'recent_activities' => [],
                'monthly_chart_data' => [],
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
                    $creditsRemaining = $usageStats['credits']['credit_balance'] ?? 0;
                    $signupBonus = $usageStats['credits']['signup_bonus_credits'] ?? 0;

                    // Calculate credits used from tool_usage or overall_usage data
                    // Approach 1: Sum tool usage if available
                    $creditsUsed = 0;
                    if (!empty($usageStats['tool_usage']) && is_array($usageStats['tool_usage'])) {
                        foreach ($usageStats['tool_usage'] as $tool) {
                            if (isset($tool['used'])) {
                                // tool['used'] represents usage count, multiply by credit rate
                                $creditsUsed += (int)($tool['used'] ?? 0);
                            }
                        }
                    }

                    // Approach 2: If no tool usage data, estimate from words_used * credit rate
                    if ($creditsUsed === 0 && !empty($usageStats['overall_usage']['words_used'])) {
                        $wordsUsed = (int)($usageStats['overall_usage']['words_used'] ?? 0);
                        // Estimate average credit rate (roughly 0.05 credits per word across all tools)
                        $avgCreditRate = 0.05;
                        $creditsUsed = (int)($wordsUsed * $avgCreditRate);
                    }

                    // Transform API response to match template expectations
                    $transformedStats = [
                        'user_email' => $usageStats['user_email'] ?? '',
                        'credits_remaining' => $creditsRemaining,
                        'credits_used' => $creditsUsed,
                        'plan_info' => $usageStats['plan_info'] ?? [],
                        'overall_usage' => $usageStats['overall_usage'] ?? [],
                        'tool_usage' => $usageStats['tool_usage'] ?? [],
                        'tool_info' => $usageStats['tool_info'] ?? [],
                        'billing_cycle' => $usageStats['billing_cycle'] ?? [],
                        'plan_limits' => $usageStats['plan_limits'] ?? [],
                        'credit_rates' => $usageStats['credits']['credit_rates'] ?? [],
                        'signup_bonus' => $signupBonus,
                    ];
                    $this->logger->info('SquadexaAI Dashboard - Transformed Stats:', [
                        'credits_remaining' => $transformedStats['credits_remaining'],
                        'credits_used' => $transformedStats['credits_used'],
                        'user_email' => $transformedStats['user_email'],
                        'plan' => $transformedStats['plan_info']['current_plan'] ?? 'unknown'
                    ]);
                    $data['usage_stats'] = $transformedStats;
                    $data['has_data'] = true;
                }
            } catch (\Exception $e) {
                $this->logger->error('Usage stats not available: ' . $e->getMessage());
            }

            // Subscription info comes from usage_stats (credit wallet model)
            // No need to call separate subscription endpoint - credits are in usage_stats

            // Fetch recent activities (all activities, pagination handled in template)
            try {
                $usageHistory = $this->apiService->getUsageHistory();
                $this->logger->info('SquadexaAI Dashboard - Usage History Response:', ['data' => $usageHistory]);

                if (!empty($usageHistory['history'])) {
                    // Get all activities, pagination will be handled in template
                    $data['recent_activities'] = $usageHistory['history'];

                    // Generate monthly chart data from activity history
                    $data['monthly_chart_data'] = $this->generateMonthlyChartData($usageHistory['history']);
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
                'monthly_chart_data' => [],
            ];
        }
    }

    /**
     * Generate monthly chart data from activity history
     *
     * @param array $activities
     * @return array
     */
    private function generateMonthlyChartData(array $activities): array
    {
        $monthlyData = [];

        // Last 8 months
        $now = new \DateTime();
        for ($i = 7; $i >= 0; $i--) {
            $date = clone $now;
            $date->modify("-$i months");
            $month = $date->format('M');
            $yearMonth = $date->format('Y-m');
            $monthlyData[$yearMonth] = ['label' => $month, 'value' => 0];
        }

        // Sum words by month
        foreach ($activities as $activity) {
            if (!empty($activity['created_at'])) {
                try {
                    $actDate = new \DateTime($activity['created_at']);
                    $yearMonth = $actDate->format('Y-m');

                    if (isset($monthlyData[$yearMonth])) {
                        $words = (int)($activity['total_words'] ?? 0);
                        $monthlyData[$yearMonth]['value'] += $words;
                    }
                } catch (\Exception $e) { // @codingStandardsIgnoreLine
                    // Skip invalid dates
                }
            }
        }

        // Return as indexed array
        return array_values($monthlyData);
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
