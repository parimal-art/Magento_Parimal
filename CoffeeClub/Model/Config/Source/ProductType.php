<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Config\Source;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Framework\Data\OptionSourceInterface;

class ProductType implements OptionSourceInterface
{
    /**
     * Return product type options for the admin multiselect field.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Type::TYPE_SIMPLE,
                'label' => __('Simple Product'),
            ],
            [
                'value' => Configurable::TYPE_CODE,
                'label' => __('Configurable Product'),
            ],
            [
                'value' => Type::TYPE_VIRTUAL,
                'label' => __('Virtual Product'),
            ],
            [
                'value' => Type::TYPE_BUNDLE,
                'label' => __('Bundle Product'),
            ],
            [
                'value' => DownloadableType::TYPE_DOWNLOADABLE,
                'label' => __('Downloadable Product'),
            ],
        ];
    }
}
