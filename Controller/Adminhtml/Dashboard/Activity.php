<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Squadkin\SquadexaAI\Service\SquadexaApiService;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Activity extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Squadkin_SquadexaAI::dashboard';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var SquadexaApiService
     */
    protected $apiService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SquadexaApiService $apiService
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SquadexaApiService $apiService,
        LoggerInterface $logger,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiService = $apiService;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
    }

    /**
     * Get paginated activity logs
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $page = (int) $this->getRequest()->getParam('page', 1);
            $perPage = 10;

            // Fetch all activities
            $usageHistory = $this->apiService->getUsageHistory();
            $allActivities = $usageHistory['history'] ?? [];

            // Calculate pagination
            $totalItems = count($allActivities);
            $totalPages = ceil($totalItems / $perPage);
            $offset = ($page - 1) * $perPage;

            // Get paginated activities
            $paginatedActivities = array_slice($allActivities, $offset, $perPage);

            // Format activities for display
            $formattedActivities = [];
            foreach ($paginatedActivities as $activity) {
                $toolType = $activity['tool_type'] ?? 'product_generator';
                $toolName = ucwords(str_replace('_', ' ', $toolType));
                $totalWords = $activity['total_words'] ?? 0;
                $timestamp = $activity['created_at'] ?? $this->dateTime->gmtDate();
                $success = $activity['success'] ?? true;
                $processingTime = isset($activity['processing_time_ms'])
                    ? round($activity['processing_time_ms'] / 1000, 2) . 's'
                    : 'N/A';

                $formattedActivities[] = [
                    'tool_name' => $toolName,
                    'endpoint' => $activity['endpoint'] ?? '/api/v1/' . $toolType,
                    'total_words' => $totalWords,
                    'timestamp' => $this->dateTime->date('d/m/Y, H:i', $timestamp),
                    'success' => $success,
                    'processing_time' => $processingTime
                ];
            }

            $result->setData([
                'success' => true,
                'activities' => $formattedActivities,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalItems,
                    'per_page' => $perPage,
                    'has_prev' => $page > 1,
                    'has_next' => $page < $totalPages
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Activity pagination error: ' . $e->getMessage());
            $result->setData([
                'success' => false,
                'message' => __('Error loading activities: %1', $e->getMessage())
            ]);
        }

        return $result;
    }
}
