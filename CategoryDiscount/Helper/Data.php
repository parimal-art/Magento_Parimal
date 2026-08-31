<?php
namespace Codilar\CategoryDiscount\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\Product;

class Data extends AbstractHelper
{
    public const int TARGET_CATEGORY_ID = 5;
    public const float DISCOUNT_PERCENTAGE = 7.0;
    public function isCategoryTargeted(?Product $product): bool
    {
        if (!$product) {
            return false;
        }

        $categoryIds = $product->getCategoryIds();

        return !empty($categoryIds) && in_array(self::TARGET_CATEGORY_ID, $categoryIds);
    }
    public function calculateDiscountPrice(float $basePrice, float $resultPrice): float
    {
        $basePrice = $basePrice <= 0 ? $resultPrice : $basePrice;
        $discountAmount = ($basePrice * self::DISCOUNT_PERCENTAGE) / 100;

        return $basePrice - $discountAmount;
    }
}
