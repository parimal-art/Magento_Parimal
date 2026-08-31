<?php
declare(strict_types=1);

namespace Codilar\QuickView\Controller\Product;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\RequestInterface;

class View implements HttpGetActionInterface
{
    public function __construct(
        protected JsonFactory $resultJsonFactory,
        protected ProductRepositoryInterface $productRepository,
        protected ImageHelper $imageHelper,
        protected RequestInterface $request
    ) {}

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $productId = $this->request->getParam('id');

        try {
            $product = $this->productRepository->getById($productId);
            $data = [
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'price' => number_format((float)$product->getFinalPrice(), 2),
                'short_description' => $product->getShortDescription(),
                'description' => $product->getDescription(),
                'product_url' => $product->getProductUrl(),
                'image' => $this->imageHelper->init($product, 'product_base_image')->getUrl(),
                'stock_status' => $product->isAvailable() ? 'In Stock' : 'Out of Stock'
            ];

            return $result->setData(['success' => true, 'product' => $data]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
