<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Field Mapping Search Results Interface
 */
interface FieldMappingSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Field Mappings
     *
     * @return FieldMappingInterface[]
     */
    public function getItems();

    /**
     * Set Field Mappings
     *
     * @param FieldMappingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

