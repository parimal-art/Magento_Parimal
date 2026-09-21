<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Block\Product;

use Codilar\CoffeeClub\Model\CategoryValidator;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Codilar\CoffeeClub\Model\ProductTypeValidator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Throwable;

class SubscriptionOption extends Template
{
    public function __construct(
        Template\Context               $context,
        protected Registry             $registry,
        protected CategoryValidator    $categoryValidator,
        protected ProductTypeValidator $productTypeValidator,
        protected FrequencyProvider    $frequencyProvider,
        array                          $data = []
    )
    {
        parent::__construct($context, $data);
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->registry->registry('current_product');
    }

    public function isEligible(): bool
    {
        $product = $this->getProduct();
        if (!$product) {
            return false;
        }
        if (!$this->productTypeValidator->isEligibleType($product)) {
            return false;
        }
        try {
            $categoryIds = $product->getCategoryIds();
            return $this->categoryValidator->isProductInAllowedCategory($categoryIds);
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function isConfigurable(): bool
    {
        $product = $this->getProduct();
        return $product !== null && $product->getTypeId() === 'configurable';
    }

    public function getFrequencies(): array
    {
        $result = [];
        foreach ($this->frequencyProvider->getAll() as $code => $data) {
            $result[] = [
                'code' => (string)$code,
                'label' => (string)$data['label'],
            ];
        }
        return $result;
    }

    /**
     * Build a map of { "attribute_code:optionId|attribute_code:optionId" => child_product_id }.
     *
     * The key is built from the CONFIGURABLE ATTRIBUTE CODES and the option value
     * IDs (which is exactly what the swatch renderer writes into the DOM as
     * `option-id`, and what PHP stores in each child product's data).
     *
     * Key is normalised (attributes sorted alphabetically) so it can be matched
     * deterministically on the client.
     *
     * @return array<string, int>
     */
    public function getChildProductMap(): array
    {
        $product = $this->getProduct();
        if (!$product || $product->getTypeId() !== 'configurable') {
            return [];
        }

        try {
            $typeInstance = $product->getTypeInstance();
            $usedProducts = $typeInstance->getUsedProducts($product);  //retrieve all child product
            $attributes = $typeInstance->getConfigurableAttributesAsArray($product);  //retrieve attributes
        } catch (Throwable $exception) {
            return [];
        }

        $attributeCodes = [];
        foreach ($attributes as $attribute) {
            if (!empty($attribute['attribute_code'])) {
                $attributeCodes[] = (string)$attribute['attribute_code'];
            }
        }

        $map = [];
        foreach ($usedProducts as $child) {
            $keyPairs = [];
            foreach ($attributeCodes as $code) {
                $value = $child->getData($code);
                if ($value !== null && $value !== '') {
                    $keyPairs[$code] = (string)$value;
                }
            }
            // Sort keys alphabetically so the key ordering is stable.
            ksort($keyPairs);

            $key = implode('|', array_map(
                static fn($k, $v) => $k . ':' . $v,
                array_keys($keyPairs),
                array_values($keyPairs)
            ));

            $map[$key] = (int)$child->getId();
        }

        return $map;
    }
}
