<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model;

use Squadkin\SquadexaAI\Api\Data\FieldMappingInterface;
use Squadkin\SquadexaAI\Api\Data\FieldMappingInterfaceFactory;
use Squadkin\SquadexaAI\Api\Data\FieldMappingSearchResultsInterface;
use Squadkin\SquadexaAI\Api\Data\FieldMappingSearchResultsInterfaceFactory;
use Squadkin\SquadexaAI\Api\FieldMappingRepositoryInterface;
use Squadkin\SquadexaAI\Model\ResourceModel\FieldMapping as FieldMappingResource;
use Squadkin\SquadexaAI\Model\ResourceModel\FieldMapping\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Field Mapping Repository
 */
class FieldMappingRepository implements FieldMappingRepositoryInterface
{
    /**
     * @var FieldMappingResource
     */
    private $resource;

    /**
     * @var FieldMappingInterfaceFactory
     */
    private $fieldMappingFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var FieldMappingSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FieldMappingResource $resource
     * @param FieldMappingInterfaceFactory $fieldMappingFactory
     * @param CollectionFactory $collectionFactory
     * @param FieldMappingSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        FieldMappingResource $resource,
        FieldMappingInterfaceFactory $fieldMappingFactory,
        CollectionFactory $collectionFactory,
        FieldMappingSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->fieldMappingFactory = $fieldMappingFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function save(FieldMappingInterface $fieldMapping): FieldMappingInterface
    {
        try {
            // If setting as default, unset other defaults
            if ($fieldMapping->getIsDefault()) {
                $this->unsetOtherDefaults($fieldMapping->getMappingId());
            }

            $this->resource->save($fieldMapping);
        } catch (\Exception $exception) {
            $this->logger->error('Error saving field mapping: ' . $exception->getMessage());
            throw new CouldNotSaveException(
                __('Could not save the field mapping: %1', $exception->getMessage()),
                $exception
            );
        }

        return $fieldMapping;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $mappingId): FieldMappingInterface
    {
        $fieldMapping = $this->fieldMappingFactory->create();
        $this->resource->load($fieldMapping, $mappingId);

        if (!$fieldMapping->getMappingId()) {
            throw new NoSuchEntityException(
                __('Field mapping with id "%1" does not exist.', $mappingId)
            );
        }

        return $fieldMapping;
    }

    /**
     * @inheritDoc
     */
    public function getDefault()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_default', 1)
            ->setPageSize(1);

        return $collection->getFirstItem()->getMappingId() ? $collection->getFirstItem() : null;
    }

    /**
     * @inheritDoc
     */
    public function getByProductTypeAndAttributeSet(string $productType, ?int $attributeSetId = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_type', $productType);

        if ($attributeSetId) {
            $collection->addFieldToFilter('attribute_set_id', $attributeSetId);
        }

        $collection->setOrder('is_default', 'DESC')
            ->setPageSize(1);

        return $collection->getFirstItem()->getMappingId() ? $collection->getFirstItem() : null;
    }

    /**
     * @inheritDoc
     */
    public function delete(FieldMappingInterface $fieldMapping): bool
    {
        try {
            $this->resource->delete($fieldMapping);
        } catch (\Exception $exception) {
            $this->logger->error('Error deleting field mapping: ' . $exception->getMessage());
            throw new CouldNotDeleteException(
                __('Could not delete the field mapping: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $mappingId): bool
    {
        return $this->delete($this->getById($mappingId));
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): FieldMappingSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * Unset other default mappings
     *
     * @param int|null $excludeMappingId
     * @return void
     */
    private function unsetOtherDefaults(?int $excludeMappingId = null): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_default', 1);

        if ($excludeMappingId) {
            $collection->addFieldToFilter('mapping_id', ['neq' => $excludeMappingId]);
        }

        foreach ($collection as $mapping) {
            $mapping->setIsDefault(false);
            $this->resource->save($mapping);
        }
    }
}

