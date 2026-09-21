<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Subscription;

use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Codilar\CoffeeClub\Model\Subscription\SubscriptionManagement;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Throwable;

class Update implements HttpPostActionInterface
{
    public function __construct(
        protected CustomerSession                 $customerSession,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected SubscriptionManagement          $subscriptionManagement,
        protected Validator                       $formKeyValidator,
        protected ManagerInterface                $messageManager,
        protected RedirectFactory                 $redirectFactory,
        protected RequestInterface                $request,
        protected Json                            $json,
        protected FrequencyProvider               $frequencyProvider
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
            $subscription = $this->subscriptionRepository->getById($subscriptionId);

            if ($subscription->getCustomerId() !== $customerId) {
                throw new LocalizedException(
                    __('You do not have permission to manage this subscription.')
                );
            }

            $frequency = (string) $this->request->getParam('frequency');
            $quantity = (int) $this->request->getParam('quantity', 1);
            $addressData = $this->request->getParam('delivery_address');

            if ($frequency !== '' && $this->frequencyProvider->isValid($frequency)) {
                $subscription->setFrequency($frequency);
            }

            if ($quantity > 0) {
                $subscription->setQuantity($quantity);
            }

            if (is_array($addressData) && !empty($addressData)) {
                // Merge the submitted fields into the existing address so that
                // country_id, region_id, firstname and lastname are preserved
                // (the edit form only sends street, city, postcode, telephone).
                $existing = json_decode((string) $subscription->getDeliveryAddress(), true);
                if (!is_array($existing)) {
                    $existing = [];
                }
                $mergedAddress = array_replace($existing, $addressData);

                $this->assertDeliveryAddressIsComplete($mergedAddress);

                $subscription->setDeliveryAddress($this->json->serialize($mergedAddress));
            }

            $this->subscriptionRepository->save($subscription);

            $this->messageManager->addSuccessMessage(
                __('Subscription updated successfully.')
            );
        } catch (Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $redirect->setPath('coffeeclub/subscription/index');
    }

    /**
     * Ensure every field Magento needs for a quote is present.
     *
     * @param array $addressData
     * @throws LocalizedException
     */
    private function assertDeliveryAddressIsComplete(array $addressData): void
    {
        $missing = [];

        $streetLine = '';
        $street = $addressData['street'] ?? [];
        if (is_array($street)) {
            $streetLine = trim((string) ($street[0] ?? ''));
        } else {
            $streetLine = trim((string) $street);
        }
        if ($streetLine === '') {
            $missing[] = __('Street Address');
        }

        if (trim((string) ($addressData['city'] ?? '')) === '') {
            $missing[] = __('City');
        }

        if (trim((string) ($addressData['postcode'] ?? '')) === '') {
            $missing[] = __('Postcode');
        }

        if (trim((string) ($addressData['telephone'] ?? '')) === '') {
            $missing[] = __('Telephone');
        }

        if (empty($missing)) {
            return;
        }

        $labels = array_map(
            static fn ($phrase) => (string) $phrase,
            $missing
        );

        throw new LocalizedException(
            __(
                'Please fill all required delivery data. The following field(s) are missing: %1.',
                implode(', ', $labels)
            )
        );
    }
}
