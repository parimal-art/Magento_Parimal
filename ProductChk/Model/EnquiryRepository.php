<?php

declare(strict_types=1);

namespace Codilar\ProductChk\Model;

use Codilar\ProductChk\Api\Data\EnquiryInterface;
use Codilar\ProductChk\Api\EnquiryRepositoryInterface;
use Codilar\ProductChk\Model\ResourceModel\Enquiry as ResourceEnquiry;
use Codilar\ProductChk\Model\ResourceModel\Enquiry\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;
use Throwable;

class EnquiryRepository implements EnquiryRepositoryInterface
{
    public function __construct(
        protected readonly ResourceEnquiry $resource,
        protected readonly LoggerInterface $logger,
        protected readonly CollectionFactory $collectionFactory
    ) {}

    /**
     * @param EnquiryInterface $enquiry
     * @return EnquiryInterface
     * @throws CouldNotSaveException
     */
    public function save(EnquiryInterface $enquiry): EnquiryInterface
    {
        try {
            $this->resource->save($enquiry);
            $this->logger->info(
                "Product Enquiry submitted successfully for SKU: {$enquiry->getSku()} by {$enquiry->getEmail()}"
            );
        } catch (Throwable $e) {
            $this->logger->error("Error saving Product Enquiry: {$e->getMessage()}");
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }

        return $enquiry;
    }

    /**
     * @return EnquiryInterface[]
     */
    public function getList(): array
    {
        return $this->collectionFactory->create()->getItems();
    }
}
