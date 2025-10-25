<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Logger;

use Psr\Log\LoggerInterface;
use Monolog\Logger as MonologLogger;

class Logger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log API request
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logApiRequest(string $message, array $context = []): void
    {
        $this->logger->info('[SquadexaAI API Request] ' . $message, $context);
    }

    /**
     * Log API response
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logApiResponse(string $message, array $context = []): void
    {
        $this->logger->info('[SquadexaAI API Response] ' . $message, $context);
    }

    /**
     * Log API error
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logApiError(string $message, array $context = []): void
    {
        $this->logger->error('[SquadexaAI API Error] ' . $message, $context);
    }

    /**
     * Log authentication events
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logAuth(string $message, array $context = []): void
    {
        $this->logger->info('[SquadexaAI Auth] ' . $message, $context);
    }

    /**
     * Log token refresh events
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logTokenRefresh(string $message, array $context = []): void
    {
        $this->logger->info('[SquadexaAI Token Refresh] ' . $message, $context);
    }

    /**
     * Log health check events
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logHealthCheck(string $message, array $context = []): void
    {
        $this->logger->info('[SquadexaAI Health Check] ' . $message, $context);
    }

    /**
     * Log debug information
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logDebug(string $message, array $context = []): void
    {
        $this->logger->debug('[SquadexaAI Debug] ' . $message, $context);
    }

    /**
     * Log warning
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning('[SquadexaAI Warning] ' . $message, $context);
    }
}
