<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class ProductTypeValidator
{
    private const CONFIG_PATH_ALLOWED_TYPES = 'coffeeclub/general/allowed_product_types';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param ProductInterface $product
     * @return bool
     */
    public function isEligibleType(ProductInterface $product): bool
    {
        return in_array($product->getTypeId(), $this->getAllowedTypes(), true);
    }

    /**
     * @param ProductInterface $product
     * @throws LocalizedException
     */
    public function assertEligibleType(ProductInterface $product): void
    {
        if (!$this->isEligibleType($product)) {
            throw new LocalizedException(
                __(
                    'Subscriptions are not available for "%1" product type.',
                    $product->getTypeId()
                )
            );
        }
    }

    /**
     * @return string[]
     */
    public function getAllowedTypes(): array
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_ALLOWED_TYPES,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($value)) {
            return ['simple', 'configurable'];
        }

        return array_map('trim', explode(',', (string) $value));
    }
}
