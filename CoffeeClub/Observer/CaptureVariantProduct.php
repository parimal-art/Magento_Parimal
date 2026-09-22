<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs just before the "create subscription" controller executes.
 *
 * Its only job is to figure out the REAL product ID the customer wants
 * to subscribe to and write it into the request as "product_id".
 *
 *   - Simple product       → product_id is already correct, do nothing.
 *   - Configurable product → the JS captured child_product_id; we validate
 *                            it belongs to the parent, then set product_id.
 */
class CaptureVariantProduct implements ObserverInterface
{
    public function __construct(
        protected RequestInterface           $request,
        protected ProductRepositoryInterface $productRepository,
        protected LoggerInterface            $logger
    )
    {
    }

    public function execute(Observer $observer): void
    {
        $parentProductId = (int)$this->request->getParam('parent_product_id');
        $childProductId = (int)$this->request->getParam('child_product_id');
        $simpleProductId = (int)$this->request->getParam('product_id');

        // ---- Simple product path -------------------------------------------
        // No parent was submitted, so product_id is already the correct ID.
        if ($parentProductId === 0) {
            $this->logger->info(sprintf(
                'CoffeeClub observer: simple product #%d',
                $simpleProductId
            ));
            return;
        }

        // ---- Configurable product path -------------------------------------
        // The JS must have written a real variant ID into child_product_id.
        if ($childProductId <= 0 || $childProductId === $parentProductId) {
            $this->logger->warning(
                'CoffeeClub observer: no variant captured for parent #' . $parentProductId
            );
            $this->request->setParam('product_id', 0);
            return;
        }

        // Verify the captured child really belongs to the parent product.
        try {
            $parent = $this->productRepository->getById($parentProductId);

            foreach ($parent->getTypeInstance()->getUsedProducts($parent) as $child) {
                if ((int)$child->getId() === $childProductId) {
                    $this->request->setParam('product_id', $childProductId);
                    $this->logger->info(sprintf(
                        'CoffeeClub observer: variant #%d belongs to parent #%d',
                        $childProductId,
                        $parentProductId
                    ));
                    return;
                }
            }

            $this->logger->warning(sprintf(
                'CoffeeClub observer: variant #%d does not belong to parent #%d',
                $childProductId,
                $parentProductId
            ));
        } catch (Throwable $exception) {
            $this->logger->error(
                'CoffeeClub observer: ' . $exception->getMessage()
            );
        }

        // Anything unexpected — force product_id to 0 so the controller
        // shows a friendly error instead of saving bad data.
        $this->request->setParam('product_id', 0);
    }
}
