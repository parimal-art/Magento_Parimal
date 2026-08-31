<?php
namespace Codilar\CategoryDiscount\Plugin;
use Codilar\CategoryDiscount\Helper\Data;
class ProductTypePricePlugin
{
    protected $helper;
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }
    public function afterGetFinalPrice(\Magento\Catalog\Model\Product\Type\Price $subject, $result, $qty, $product)
    {
        if ($this->helper->isCategoryTargeted($product) == false) {
            return $result;
        }
        return $this->helper->calculateDiscountPrice((float)$product->getPrice(), (float)$result);
    }
}
