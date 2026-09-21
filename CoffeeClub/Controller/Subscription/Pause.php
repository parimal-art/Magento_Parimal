<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Subscription;

use Codilar\CoffeeClub\Model\Subscription\SubscriptionManagement;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Message\ManagerInterface;

class Pause implements HttpPostActionInterface
{
    public function __construct(
        protected CustomerSession $customerSession,
        protected SubscriptionManagement $subscriptionManagement,
        protected Validator $formKeyValidator,
        protected ManagerInterface $messageManager,
        protected RedirectFactory $redirectFactory,
        protected RequestInterface $request
    ) {
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $redirect->setPath('customer/account/login');
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(
                __('Invalid form key. Please try again.')
            );
            return $redirect->setPath('coffeeclub/subscription/index');
        }

        $subscriptionId = (int) $this->request->getParam('subscription_id');
        $customerId = (int) $this->customerSession->getCustomerId();

        try {
            $this->subscriptionManagement->pause($subscriptionId, $customerId);
            $this->messageManager->addSuccessMessage(__('Subscription paused.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $redirect->setPath('coffeeclub/subscription/index');
    }
}
