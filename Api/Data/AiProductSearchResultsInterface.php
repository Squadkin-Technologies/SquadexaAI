<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Api\Data;

interface AiProductSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get AiProduct list.
     * @return \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface[]
     */
    public function getItems();

    /**
     * Set AiProduct list.
     * @param \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
} 