<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api\Data;

interface AiProductSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get AiProduct list
     *
     * @return \Squadkin\SquadexaAI\Api\Data\AiProductInterface[]
     */
    public function getItems();

    /**
     * Set AiProduct list
     *
     * @param \Squadkin\SquadexaAI\Api\Data\AiProductInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
