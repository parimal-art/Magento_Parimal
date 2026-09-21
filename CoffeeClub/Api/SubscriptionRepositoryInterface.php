<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Api;

use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SubscriptionRepositoryInterface
{
    /**
     * Save subscription
     *
     * @param SubscriptionInterface $subscription
     * @return SubscriptionInterface
     * @throws CouldNotSaveException
     */
    public function save(SubscriptionInterface $subscription): SubscriptionInterface;

    /**
     * Get subscription by ID
     *
     * @param int $subscriptionId
     * @return SubscriptionInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $subscriptionId): SubscriptionInterface;

    /**
     * Get list of subscriptions
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete subscription
     *
     * @param SubscriptionInterface $subscription
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(SubscriptionInterface $subscription): bool;

    /**
     * Delete subscription by ID
     *
     * @param int $subscriptionId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $subscriptionId): bool;
}
