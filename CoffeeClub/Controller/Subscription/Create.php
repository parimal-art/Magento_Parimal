<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Controller\Subscription;

use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\CategoryValidator;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Codilar\CoffeeClub\Model\ProductTypeValidator;
use Codilar\CoffeeClub\Model\Subscription\EmailNotifier;
use Codilar\CoffeeClub\Model\Subscription\SubscriptionManagement;
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
        protected SubscriptionManagement          $subscriptionManagement,
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

        $parentProductId = (int)$this->request->getParam('parent_product_id');
        $childProductId = (int)$this->request->getParam('child_product_id');
        $simpleProductId = (int)$this->request->getParam('product_id');
        $quantity = max(1, (int)$this->request->getParam('quantity', 1));
        $frequency = (string)$this->request->getParam('frequency');

        try {
            if ($parentProductId > 0) {
                // -------- Configurable product path --------

                // Guard #1: a real variant must have been captured client-side.
                if ($childProductId <= 0) {
                    throw new LocalizedException(
                        __('Please select a product variant before starting a subscription.')
                    );
                }

                // Guard #2: the parent ID must never be submitted as a child.
                // Magento initialises the hidden #product input with the parent
                // ID, so if the customer never completed a selection this is
                // exactly what ends up here.
                if ($childProductId === $parentProductId) {
                    throw new LocalizedException(
                        __('Please select a product variant before starting a subscription.')
                    );
                }

                $parentProduct = $this->productRepository->getById($parentProductId);
                $product = $this->productRepository->getById($childProductId);

                // Guard #3: the submitted child must actually belong to the parent.
                if (!$this->isChildOfParent($parentProduct, $childProductId)) {
                    throw new LocalizedException(
                        __('Selected variant does not belong to this product.')
                    );
                }

                $productId = $childProductId;
            } else {
                // -------- Simple product path --------
                $productId = $simpleProductId;
                $product = $this->productRepository->getById($productId);
                $parentProductId = null;
            }

            $categoryIds = $product->getCategoryIds();
            if (!$this->categoryValidator->isProductInAllowedCategory($categoryIds)) {
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
                throw new LocalizedException(
                    __('Invalid delivery frequency.')
                );
            }

            $customerId = (int)$this->customerSession->getCustomerId();
            $customer = $this->customerSession->getCustomer();

            // Build the delivery address from the customer's default shipping address.
            // Throws a friendly exception if the customer has none, or if it is incomplete.
            $addressData = $this->buildAddressFromCustomer($customer);
            $this->assertDeliveryAddressIsComplete($addressData);

            /** @var SubscriptionInterface $subscription */
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

            // Save, then notify. The repository returns the authoritative object.
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
                ['id' => $parentProductId ?: $simpleProductId]
            );
        }
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $parent
     * @param int $childId
     * @return bool
     */
    private function isChildOfParent($parent, int $childId): bool
    {
        $children = $parent->getTypeInstance()->getUsedProducts($parent);
        foreach ($children as $child) {
            if ((int)$child->getId() === $childId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build address data from the customer's default shipping address.
     *
     * If the customer has no default shipping address, a clear exception is thrown
     * so the subscription is never created with an empty delivery address.
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
     * Ensure every field that Magento needs for a quote is present.
     *
     * The fields validated here mirror exactly what QuoteManagement::submit()
     * requires; without them the cron-created order fails.
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
            $streetLine = trim((string)($street[0] ?? ''));
        } else {
            $streetLine = trim((string)$street);
        }
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
