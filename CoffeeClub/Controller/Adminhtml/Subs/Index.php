<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Adminhtml\Subs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Codilar_CoffeeClub::subs';

    public function __construct(
        Context                      $context,
        private readonly PageFactory $pageFactory
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Codilar_CoffeeClub::subs');
        $page->getConfig()->getTitle()->prepend(__('Codilar Subscriptions'));
        return $page;
    }
}
