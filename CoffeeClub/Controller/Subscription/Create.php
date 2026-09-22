<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Subscription;

use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\CategoryValidator;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Codilar\CoffeeClub\Model\ProductTypeValidator;
use Codilar\CoffeeClub\Model\Subscription\EmailNotifier;
use Codilar\CoffeeClub\Model\SubscriptionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Throwable;

class Create implements HttpPostActionInterface
{
    public function __construct(
        protected CustomerSession                 $customerSession,
        protected ProductRepositoryInterface      $productRepository,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected SubscriptionFactory             $subscriptionFactory,
        protected CategoryValidator               $categoryValidator,
        protected ProductTypeValidator            $productTypeValidator,
        protected AddressRepositoryInterface      $addressRepository,
        protected Validator                       $formKeyValidator,
        protected ManagerInterface                $messageManager,
        protected RedirectFactory                 $redirectFactory,
        protected RequestInterface                $request,
        protected Json                            $json,
        protected FrequencyProvider               $frequencyProvider,
        protected EmailNotifier                   $emailNotifier
    )
    {
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(
                __('Please log in to create a subscription.')
            );
            return $redirect->setPath('customer/account/login');
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(
                __('Invalid form key. Please try again.')
            );
            return $redirect->setPath('coffeeclub/subscription/index');
        }

        // The observer "CaptureVariantProduct" has already resolved the real
        // product ID and put it into "product_id".
        $productId = (int)$this->request->getParam('product_id');
        $parentProductId = (int)$this->request->getParam('parent_product_id') ?: null;
        $quantity = max(1, (int)$this->request->getParam('quantity', 1));
        $frequency = (string)$this->request->getParam('frequency');

        try {
            if ($productId <= 0) {
                throw new LocalizedException(
                    __('Please select a product variant before starting a subscription.')
                );
            }

            $product = $this->productRepository->getById($productId);

            if (!$this->categoryValidator->isProductInAllowedCategory($product->getCategoryIds())) {
                throw new LocalizedException(
                    __('Subscriptions are not available for this product category.')
                );
            }

            $this->productTypeValidator->assertEligibleType($product);

            if (!$product->isSalable()) {
                throw new LocalizedException(
                    __('This product is currently out of stock.')
                );
            }

            if (!$this->frequencyProvider->isValid($frequency)) {
                throw new LocalizedException(__('Invalid delivery frequency.'));
            }

            $customerId = (int)$this->customerSession->getCustomerId();
            $customer = $this->customerSession->getCustomer();

            $addressData = $this->buildAddressFromCustomer($customer);
            $this->assertDeliveryAddressIsComplete($addressData);

            $subscription = $this->subscriptionFactory->create();
            $subscription->setCustomerId($customerId);
            $subscription->setProductId($productId);
            $subscription->setParentProductId($parentProductId);
            $subscription->setQuantity($quantity);
            $subscription->setAgreedPrice((float)$product->getFinalPrice());
            $subscription->setFrequency($frequency);
            $subscription->setDeliveryAddress($this->json->serialize($addressData));
            $subscription->setStatus(SubscriptionInterface::STATUS_ACTIVE);
            $subscription->setNextDueDate((new \DateTime())->format('Y-m-d'));
            $subscription->setConsecutiveFailureCount(0);

            $saved = $this->subscriptionRepository->save($subscription);
            $this->emailNotifier->notifyStarted($saved);

            $this->messageManager->addSuccessMessage(
                __('Your subscription has been created.')
            );
            return $redirect->setPath('coffeeclub/subscription/index');
        } catch (Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $redirect->setPath(
                'catalog/product/view',
                ['id' => $parentProductId ?: $productId]
            );
        }
    }

    /**
     * Build address data from the customer's default shipping address.
     *
     * @param \Magento\Customer\Model\Data\Customer $customer
     * @return array
     * @throws LocalizedException
     */
    private function buildAddressFromCustomer($customer): array
    {
        $defaultShippingId = $customer->getDefaultShipping();
        if (!$defaultShippingId) {
            throw new LocalizedException(
                __(
                    'Before starting a subscription, kindly set a default shipping address ' .
                    'in your account. Go to My Account → Address Book to add one.'
                )
            );
        }

        try {
            $defaultAddress = $this->addressRepository->getById((int)$defaultShippingId);
        } catch (Throwable $exception) {
            throw new LocalizedException(
                __(
                    'We could not load your default shipping address. ' .
                    'Please check your address book and try again.'
                )
            );
        }

        $street = $defaultAddress->getStreet();

        $address = [
            'firstname' => (string)($defaultAddress->getFirstname() ?: $customer->getFirstname()),
            'lastname' => (string)($defaultAddress->getLastname() ?: $customer->getLastname()),
            'street' => is_array($street) ? $street : [(string)$street],
            'city' => (string)$defaultAddress->getCity(),
            'postcode' => (string)$defaultAddress->getPostcode(),
            'country_id' => (string)$defaultAddress->getCountryId(),
            'telephone' => (string)$defaultAddress->getTelephone(),
            'region' => '',
            'region_id' => (int)$defaultAddress->getRegionId(),
        ];

        $region = $defaultAddress->getRegion();
        if ($region instanceof RegionInterface) {
            $address['region'] = (string)$region->getRegion();
            if (empty($address['region_id'])) {
                $address['region_id'] = (int)$region->getRegionId();
            }
        } elseif (is_string($region)) {
            $address['region'] = $region;
        }

        return $address;
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

        $street = $addressData['street'] ?? [];
        $streetLine = is_array($street)
            ? trim((string)($street[0] ?? ''))
            : trim((string)$street);

        if ($streetLine === '') {
            $missing[] = __('Street Address');
        }
        if (trim((string)($addressData['city'] ?? '')) === '') {
            $missing[] = __('City');
        }
        if (trim((string)($addressData['postcode'] ?? '')) === '') {
            $missing[] = __('Postcode');
        }
        if (trim((string)($addressData['telephone'] ?? '')) === '') {
            $missing[] = __('Telephone');
        }

        if (empty($missing)) {
            return;
        }

        $labels = array_map(
            static fn($phrase) => (string)$phrase,
            $missing
        );

        throw new LocalizedException(
            __(
                'Before starting a subscription, kindly fill all required delivery data. ' .
                'The following field(s) are missing from your default shipping address: %1. ' .
                'Please update your address in My Account → Address Book.',
                implode(', ', $labels)
            )
        );
    }
}
