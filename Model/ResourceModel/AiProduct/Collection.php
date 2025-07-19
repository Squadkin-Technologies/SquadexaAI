<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Model\ResourceModel\AiProduct;

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
            \Squadkin\AIAutoProductBuilder\Model\AiProduct::class,
            \Squadkin\AIAutoProductBuilder\Model\ResourceModel\AiProduct::class
        );
    }
} 