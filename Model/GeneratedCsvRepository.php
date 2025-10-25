<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterface;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvInterfaceFactory;
use Squadkin\SquadexaAI\Api\Data\GeneratedCsvSearchResultsInterfaceFactory;
use Squadkin\SquadexaAI\Api\GeneratedCsvRepositoryInterface;
use Squadkin\SquadexaAI\Model\ResourceModel\GeneratedCsv as ResourceGeneratedCsv;
use Squadkin\SquadexaAI\Model\ResourceModel\GeneratedCsv\CollectionFactory as GeneratedCsvCollectionFactory;

class GeneratedCsvRepository implements GeneratedCsvRepositoryInterface
{

    /**
     * @var GeneratedCsvCollectionFactory
     */
    protected $generatedCsvCollectionFactory;

    /**
     * @var GeneratedCsv
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ResourceGeneratedCsv
     */
    protected $resource;

    /**
     * @var GeneratedCsvInterfaceFactory
     */
    protected $generatedCsvFactory;


    /**
     * @param ResourceGeneratedCsv $resource
     * @param GeneratedCsvInterfaceFactory $generatedCsvFactory
     * @param GeneratedCsvCollectionFactory $generatedCsvCollectionFactory
     * @param GeneratedCsvSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceGeneratedCsv $resource,
        GeneratedCsvInterfaceFactory $generatedCsvFactory,
        GeneratedCsvCollectionFactory $generatedCsvCollectionFactory,
        GeneratedCsvSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->generatedCsvFactory = $generatedCsvFactory;
        $this->generatedCsvCollectionFactory = $generatedCsvCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(GeneratedCsvInterface $generatedCsv)
    {
        try {
            $this->resource->save($generatedCsv);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the generatedCsv: %1',
                $exception->getMessage()
            ));
        }
        return $generatedCsv;
    }

    /**
     * @inheritDoc
     */
    public function get($generatedCsvId)
    {
        $generatedCsv = $this->generatedCsvFactory->create();
        $this->resource->load($generatedCsv, $generatedCsvId);
        if (!$generatedCsv->getId()) {
            throw new NoSuchEntityException(__('GeneratedCsv with id "%1" does not exist.', $generatedCsvId));
        }
        return $generatedCsv;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->generatedCsvCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(GeneratedCsvInterface $generatedCsv)
    {
        try {
            $generatedCsvModel = $this->generatedCsvFactory->create();
            $this->resource->load($generatedCsvModel, $generatedCsv->getGeneratedcsvId());
            $this->resource->delete($generatedCsvModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the GeneratedCsv: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($generatedCsvId)
    {
        return $this->delete($this->get($generatedCsvId));
    }
}

