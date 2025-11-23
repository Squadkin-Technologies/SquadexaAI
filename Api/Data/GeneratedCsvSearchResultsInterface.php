<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api\Data;

interface GeneratedCsvSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get GeneratedCsv list.
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface[]
     */
    public function getItems();

    /**
     * Set input_file_name list.
     * @param \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
