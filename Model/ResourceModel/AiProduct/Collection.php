<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model\ResourceModel\AiProduct;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'aiproduct_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Squadkin\SquadexaAI\Model\AiProduct::class,
            \Squadkin\SquadexaAI\Model\ResourceModel\AiProduct::class
        );
    }
} 