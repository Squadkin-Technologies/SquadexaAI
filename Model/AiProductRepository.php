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
use Squadkin\SquadexaAI\Api\Data\AiProductInterface;
use Squadkin\SquadexaAI\Api\Data\AiProductInterfaceFactory;
use Squadkin\SquadexaAI\Api\Data\AiProductSearchResultsInterfaceFactory;
use Squadkin\SquadexaAI\Api\AiProductRepositoryInterface;
use Squadkin\SquadexaAI\Model\ResourceModel\AiProduct as ResourceAiProduct;
use Squadkin\SquadexaAI\Model\ResourceModel\AiProduct\CollectionFactory as AiProductCollectionFactory;

class AiProductRepository implements AiProductRepositoryInterface
{
    /**
     * @var AiProductCollectionFactory
     */
    protected $aiProductCollectionFactory;

    /**
     * @var AiProductSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ResourceAiProduct
     */
    protected $resource;

    /**
     * @var AiProductInterfaceFactory
     */
    protected $aiProductFactory;

    /**
     * @param ResourceAiProduct $resource
     * @param AiProductInterfaceFactory $aiProductFactory
     * @param AiProductCollectionFactory $aiProductCollectionFactory
     * @param AiProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceAiProduct $resource,
        AiProductInterfaceFactory $aiProductFactory,
        AiProductCollectionFactory $aiProductCollectionFactory,
        AiProductSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->aiProductFactory = $aiProductFactory;
        $this->aiProductCollectionFactory = $aiProductCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(AiProductInterface $aiProduct)
    {
        try {
            $this->resource->save($aiProduct);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the aiProduct: %1',
                $exception->getMessage()
            ));
        }
        return $aiProduct;
    }

    /**
     * @inheritDoc
     */
    public function get($aiProductId)
    {
        $aiProduct = $this->aiProductFactory->create();
        $this->resource->load($aiProduct, $aiProductId);
        if (!$aiProduct->getId()) {
            throw new NoSuchEntityException(__('AiProduct with id "%1" does not exist.', $aiProductId));
        }
        return $aiProduct;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->aiProductCollectionFactory->create();
        
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
    public function delete(AiProductInterface $aiProduct)
    {
        try {
            $aiProductModel = $this->aiProductFactory->create();
            $this->resource->load($aiProductModel, $aiProduct->getAiproductId());
            $this->resource->delete($aiProductModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the AiProduct: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($aiProductId)
    {
        return $this->delete($this->get($aiProductId));
    }
}
