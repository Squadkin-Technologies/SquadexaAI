<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\AIAutoProductBuilder\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface AiProductRepositoryInterface
{
    /**
     * Save AiProduct
     * @param \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface $aiProduct
     * @return \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface $aiProduct
    );

    /**
     * Retrieve AiProduct
     * @param string $aiproductId
     * @return \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($aiproductId);

    /**
     * Retrieve AiProduct matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Squadkin\AIAutoProductBuilder\Api\Data\AiProductSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete AiProduct
     * @param \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface $aiProduct
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Squadkin\AIAutoProductBuilder\Api\Data\AiProductInterface $aiProduct
    );

    /**
     * Delete AiProduct by ID
     * @param string $aiproductId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($aiproductId);
} 