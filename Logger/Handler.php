<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/squadexa_ai_api.log';
}
