<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model;

use Codilar\CoffeeClub\Api\Data\RunLogInterface;
use Codilar\CoffeeClub\Api\RunLogRepositoryInterface;
use Codilar\CoffeeClub\Model\ResourceModel\RunLog as RunLogResource;
use Magento\Framework\Api\SearchResultsInterface;
use Codilar\CoffeeClub\Model\ResourceModel\RunLog\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;

class RunLogRepository implements RunLogRepositoryInterface
{
    public function __construct(
        protected RunLogResource                $resource,
        protected CollectionFactory             $collectionFactory,
        protected SearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface  $collectionProcessor
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function save(RunLogInterface $runLog): RunLogInterface
    {
        try {
            /** @var RunLog $runLog */
            $this->resource->save($runLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the run log: %1', $exception->getMessage()),
                $exception
            );
        }

        return $runLog;
    }

    /**
     * @inheritDoc
     */
    public function getBySubscriptionId(int $subscriptionId, int $limit = 50): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(RunLogInterface::SUBSCRIPTION_ID, $subscriptionId)
            ->setOrder(RunLogInterface::RUN_DATE, 'DESC')
            ->setPageSize($limit);

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
