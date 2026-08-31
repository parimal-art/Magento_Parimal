<?php

namespace Codilar\SliderProduct\Block;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class RelatedProduct extends Template
{
    public function __construct(
        Context $context,
        protected CollectionFactory $productCollectionFactory,
        protected CategoryCollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCategoryProducts()
    {
        if ($this->getRequest()->getFullActionName() !== 'catalog_product_view') {
            return [];
        }

        // Get the current product from the page URL
        $productId = $this->getRequest()->getParam('id');
        // var_dump($productId);
        // die;

        if (is_array($productId)) {
            $productId = end($productId);
        }

        if (!$productId) {
            return [];
        }

        $currentProduct = $this->productCollectionFactory
            ->create()
            ->addFieldToFilter('entity_id', $productId)
            ->getFirstItem();

        // var_dump($currentProduct);
        // die;

        if (!$currentProduct->getId()) {
            return [];
        }

        // Get all category ids assigned to this product (this includes parent categories too)
        $categoryIds = $currentProduct->getCategoryIds();
        // var_dump($categoryIds);
        // die();

        if (!$categoryIds) {
            return [];
        }

        // code to find lastest subcategory
        $deepestCategory = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToSelect('level')
            ->addFieldToFilter('entity_id', ['in' => $categoryIds])
            ->setOrder('level', 'DESC')
            ->getFirstItem();

        // var_dump($deepestCategory->getData());
        // die();

        if (!$deepestCategory->getId()) {
            return [];
        }

        // Get other products
        return $this->productCollectionFactory
            ->create()
            ->addCategoriesFilter(['in' => [$deepestCategory->getId()]])
            ->addAttributeToFilter('entity_id', ['neq' => $productId])
            ->addAttributeToSelect(['name', 'price', 'small_image', 'url_key'])
            ->addUrlRewrite();
    }
}
