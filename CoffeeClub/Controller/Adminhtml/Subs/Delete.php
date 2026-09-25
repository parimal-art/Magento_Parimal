<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Adminhtml\Subs;

use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Codilar_CoffeeClub::subs';

    public function __construct(
        Context                                          $context,
        private readonly SubscriptionRepositoryInterface $subsRepository
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $subscriptionId = (int)$this->getRequest()->getParam('id');
//        print_r($subscriptionId);
//        exit;

        if (!$subscriptionId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a subscription to delete.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->subsRepository->deleteById($subscriptionId);
            $this->messageManager->addSuccessMessage(__('The subscription has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(
                __('Subscription with ID "%1" does not exist.', $subscriptionId)
            );
        } catch (CouldNotDeleteException $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while deleting the subscription: %1', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while deleting the subscription.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
