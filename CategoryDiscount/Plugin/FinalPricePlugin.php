<?php
namespace Codilar\CategoryDiscount\Plugin;
use Codilar\CategoryDiscount\Helper\Data;

class FinalPricePlugin
{
    protected $helper;
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }
    public function afterGetValue(\Magento\Catalog\Pricing\Price\FinalPrice $subject, $result)
    {
        $product = $subject->getProduct();

        if ($this->helper->isCategoryTargeted($product) == false) {
            return $result;
        }
        return $this->helper->calculateDiscountPrice((float)$product->getPrice(), (float)$result);
    }
}
