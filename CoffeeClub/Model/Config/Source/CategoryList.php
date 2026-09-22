<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class CategoryList implements OptionSourceInterface
{
    /**
     * Character used to indent child categories inside the dropdown.
     * Non-breaking spaces so they render correctly in HTML.
     */
    private const INDENT = "\u{00A0}\u{00A0}\u{00A0}";

    public function __construct(
        protected CollectionFactory     $categoryCollectionFactory,
        protected StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * Build a hierarchical option list for the admin multiselect.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $store = $this->storeManager->getDefaultStoreView();
        $storeId = (int)$store->getId();
        $rootCategoryId = (int)$store->getRootCategoryId();

        // Load all ACTIVE categories that live under the store's root category.
        $collection = $this->categoryCollectionFactory->create();
        $collection
            ->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'parent_id'])
            ->addIsActiveFilter()
            ->addPathFilter('^1/' . $rootCategoryId . '/')
            ->setOrder('position', 'ASC');

        $categoriesById = [];
        $childrenByParent = [];

        foreach ($collection as $category) {
            $id = (int)$category->getId();
            $parentId = (int)$category->getParentId();

            $categoriesById[$id] = $category;
            $childrenByParent[$parentId][] = $id;
        }

        $options = [];
        $this->buildOptions(
            $rootCategoryId,
            $childrenByParent,
            $categoriesById,
            $options,
            0
        );

        return $options;
    }

    /**
     * Recursively flatten the category tree with indentation.
     *
     * @param int $parentId
     * @param array $childrenByParent
     * @param array $categoriesById
     * @param array $options
     * @param int $level
     * @return void
     */
    private function buildOptions(
        int   $parentId,
        array $childrenByParent,
        array $categoriesById,
        array &$options,
        int   $level
    ): void
    {
        if (empty($childrenByParent[$parentId])) {
            return;
        }

        foreach ($childrenByParent[$parentId] as $childId) {
            $category = $categoriesById[$childId] ?? null;
            if (!$category) {
                continue;
            }

            $indent = str_repeat(self::INDENT, $level);
            $name = (string)$category->getName();

            $options[] = [
                'value' => (int)$childId,
                'label' => $indent . $name . ' (ID: ' . $childId . ')',
            ];

            $this->buildOptions(
                $childId,
                $childrenByParent,
                $categoriesById,
                $options,
                $level + 1
            );
        }
    }
}
