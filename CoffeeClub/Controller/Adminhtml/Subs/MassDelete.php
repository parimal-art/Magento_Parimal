<?php

declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Adminhtml\Subs;

use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\ResourceModel\Subscription\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'Codilar_CoffeeClub::subs';

    public function __construct(
        Context                                          $context,
        private readonly Filter                          $filter,
        private readonly CollectionFactory               $collectionFactory,
        private readonly SubscriptionRepositoryInterface $subsRepository
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection(
            $this->collectionFactory->create()
        );

//        echo '<pre>';
//        print_r($collection->getData());
//        echo '</pre>';
//        exit;

        $deleted = 0;

        foreach ($collection as $subs) {
            try {
                $this->subsRepository->deleteById(
                    (int)$subs->getId()
                );

                $deleted++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Unable to delete FAQ ID %1.', $subs->getId())
                );
            }
        }

        if ($deleted > 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 FAQ(s) deleted successfully.', $deleted)
            );
        }

        return $this->resultFactory
            ->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('*/*/index');
    }
}
