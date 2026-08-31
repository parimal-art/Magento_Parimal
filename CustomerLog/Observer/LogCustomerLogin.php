<?php
declare(strict_types=1);
namespace Codilar\CustomerLog\Observer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
class LogCustomerLogin implements ObserverInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DateTime $dateTime
    ) {}
    public function execute(Observer $observer): void
    {
        try {
            $customer = $observer->getEvent()->getCustomer();

            if (!$customer || !$customer->getId()) {
                return;
            }

            $customerId = $customer->getId();
            $customerEmail = $customer->getEmail();
            $logData = [
                'customer_id' => $customerId,
                'email'       => $customerEmail,
                'login_time'  => $this->dateTime->gmtDate()
            ];
            $this->logger->info(
                sprintf(
                    'Customer Login - ID: %s | Email: %s | Date/Time: %s (UTC)',
                    $logData['customer_id'],
                    $logData['email'],
                    $logData['login_time']
                ),
                $logData
            );
        } catch (\Throwable $e) {
            $this->logger->error('Error logging customer login: ' . $e->getMessage());
        }
    }
}
