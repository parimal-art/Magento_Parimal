<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Api;

use Codilar\CoffeeClub\Api\Data\RunLogInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;

interface RunLogRepositoryInterface
{
    /**
     * Save run log
     *
     * @param RunLogInterface $runLog
     * @return RunLogInterface
     * @throws CouldNotSaveException
     */
    public function save(RunLogInterface $runLog): RunLogInterface;

    /**
     * Get run logs by subscription ID
     *
     * @param int $subscriptionId
     * @param int $limit
     * @return RunLogInterface[]
     */
    public function getBySubscriptionId(int $subscriptionId, int $limit = 50): array;

    /**
     * Get list of run logs
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
