<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model\ResourceModel\FieldMapping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Squadkin\SquadexaAI\Model\FieldMapping;
use Squadkin\SquadexaAI\Model\ResourceModel\FieldMapping as FieldMappingResource;

/**
 * Field Mapping Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'mapping_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(FieldMapping::class, FieldMappingResource::class);
    }
}

