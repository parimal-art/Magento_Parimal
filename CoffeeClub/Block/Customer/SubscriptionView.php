<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Block\Customer;

use Codilar\CoffeeClub\Api\RunLogRepositoryInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;

class SubscriptionView extends Template
{
    public function __construct(
        Template\Context                          $context,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected RunLogRepositoryInterface       $runLogRepository,
        protected RequestInterface                $request,
        protected FrequencyProvider               $frequencyProvider,
        array                                     $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getSubscription()
    {
        $id = (int) $this->request->getParam('subscription_id');
        try {
            return $this->subscriptionRepository->getById($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getSubscriptionHistory(int $subscriptionId)
    {
        return $this->runLogRepository->getBySubscriptionId($subscriptionId, 20);
    }

    /**
     * Return configured subscription frequencies for the edit form dropdown.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function getFrequencies(): array
    {
        $result = [];
        foreach ($this->frequencyProvider->getAll() as $code => $data) {
            $result[] = [
                'code'  => (string) $code,
                'label' => (string) $data['label'],
            ];
        }
        return $result;
    }
}
