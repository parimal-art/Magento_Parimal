<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Subscription;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    public function __construct(
        protected CustomerSession $customerSession,
        protected PageFactory $pageFactory,
        protected RedirectFactory $redirectFactory,
        protected RequestInterface $request
    ) {
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()
                ->setPath('customer/account/login');
        }

        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Coffee Club Subscriptions'));

        return $resultPage;
    }
}
