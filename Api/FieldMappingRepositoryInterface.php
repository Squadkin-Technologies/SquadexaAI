<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Api;

use Squadkin\SquadexaAI\Api\Data\FieldMappingInterface;
use Squadkin\SquadexaAI\Api\Data\FieldMappingSearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Field Mapping Repository Interface
 */
interface FieldMappingRepositoryInterface
{
    /**
     * Save Field Mapping
     *
     * @param FieldMappingInterface $fieldMapping
     * @return FieldMappingInterface
     * @throws CouldNotSaveException
     */
    public function save(FieldMappingInterface $fieldMapping): FieldMappingInterface;

    /**
     * Get Field Mapping by ID
     *
     * @param int $mappingId
     * @return FieldMappingInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $mappingId): FieldMappingInterface;

    /**
     * Get Default Field Mapping
     *
     * @return FieldMappingInterface|null
     */
    public function getDefault();

    /**
     * Get Field Mapping by Product Type and Attribute Set
     *
     * @param string $productType
     * @param int|null $attributeSetId
     * @return FieldMappingInterface|null
     */
    public function getByProductTypeAndAttributeSet(string $productType, ?int $attributeSetId = null);

    /**
     * Delete Field Mapping
     *
     * @param FieldMappingInterface $fieldMapping
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(FieldMappingInterface $fieldMapping): bool;

    /**
     * Delete Field Mapping by ID
     *
     * @param int $mappingId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $mappingId): bool;

    /**
     * Get List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return FieldMappingSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): FieldMappingSearchResultsInterface;
}

