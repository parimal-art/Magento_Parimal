<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CategoryValidator
{
    private const CONFIG_PATH_ALLOWED_CATEGORIES = 'coffeeclub/general/allowed_categories';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if a product belongs to any allowed subscription category.
     *
     * @param array $categoryIds
     * @return bool
     */
    public function isProductInAllowedCategory(array $categoryIds): bool
    {
        if (empty($categoryIds)) {
            return false;
        }

        $allowedCategories = $this->getAllowedCategoryIds();

        if (empty($allowedCategories)) {
            return false;
        }

        return !empty(array_intersect(
            array_map('intval', $categoryIds),
            $allowedCategories
        ));
    }

    /**
     * Get configured allowed category IDs.
     *
     * @return int[]
     */
    public function getAllowedCategoryIds(): array
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_ALLOWED_CATEGORIES,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($value)) {
            return [];
        }

        return array_map('intval', explode(',', (string) $value));
    }
}
