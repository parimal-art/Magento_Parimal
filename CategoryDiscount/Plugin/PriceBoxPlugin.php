<?php
namespace Codilar\CategoryDiscount\Plugin;
use Codilar\CategoryDiscount\Helper\Data;
class PriceBoxPlugin
{
    protected $helper;
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }
    public function afterGetTemplate(\Magento\Catalog\Pricing\Render\FinalPriceBox $subject, $result)
    {
        $product = $subject->getSaleableItem();
        if ($this->helper->isCategoryTargeted($product) == true) {
            return 'Codilar_CategoryDiscount::product/price/final_price.phtml';
        }
        return $result;
    }
}
