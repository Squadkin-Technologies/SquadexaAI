<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface GeneratedCsvRepositoryInterface
{

    /**
     * Save GeneratedCsv
     * @param \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface $generatedCsv
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface $generatedCsv
    );

    /**
     * Retrieve GeneratedCsv
     * @param string $generatedcsvId
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($generatedcsvId);

    /**
     * Retrieve GeneratedCsv matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Squadkin\SquadexaAI\Api\Data\GeneratedCsvSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete GeneratedCsv
     * @param \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface $generatedCsv
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface $generatedCsv
    );

    /**
     * Delete GeneratedCsv by ID
     * @param string $generatedcsvId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($generatedcsvId);
}

