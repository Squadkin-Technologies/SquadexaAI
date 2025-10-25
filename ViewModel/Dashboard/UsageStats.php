<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\ViewModel\Dashboard;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Squadkin\SquadexaAI\Service\SquadexaApiService;

class UsageStats implements ArgumentInterface
{
    /**
     * @var SquadexaApiService
     */
    private $apiService;

    /**
     * @param SquadexaApiService $apiService
     */
    public function __construct(
        SquadexaApiService $apiService
    ) {
        $this->apiService = $apiService;
    }

    /**
     * Get API service
     *
     * @return SquadexaApiService
     */
    public function getApiService(): SquadexaApiService
    {
        return $this->apiService;
    }
}
