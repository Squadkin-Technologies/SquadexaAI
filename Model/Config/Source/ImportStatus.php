<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ImportStatus implements OptionSourceInterface
{
    /**
     * Import status options
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => __('Pending')],
            ['value' => self::STATUS_PROCESSING, 'label' => __('Processing')],
            ['value' => self::STATUS_COMPLETED, 'label' => __('Completed')],
            ['value' => self::STATUS_FAILED, 'label' => __('Failed')]
        ];
    }

    /**
     * Get options as key-value pairs
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_PROCESSING => __('Processing'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_FAILED => __('Failed')
        ];
    }
}
