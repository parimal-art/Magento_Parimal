<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Subscription;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;

class View implements HttpGetActionInterface
{
    public function __construct(
        protected CustomerSession $customerSession,
        protected PageFactory $pageFactory,
        protected RedirectFactory $redirectFactory,
        protected RequestInterface $request,
        protected ManagerInterface $messageManager,
        protected SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }

        $subscriptionId = (int) $this->request->getParam('subscription_id');

        try {
            $subscription = $this->subscriptionRepository->getById($subscriptionId);
            if ($subscription->getCustomerId() !== (int) $this->customerSession->getCustomerId()) {
                throw new \Exception(__('You do not have permission.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Subscription not found.'));
            return $this->redirectFactory->create()->setPath('*/*/index');
        }

        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Manage Subscription #%1', $subscriptionId));
        return $resultPage;
    }
}
