<?php

declare(strict_types=1);

namespace Codilar\ProductChk\Controller\Enquiry;

use Codilar\ProductChk\Api\Data\EnquiryInterfaceFactory;
use Codilar\ProductChk\Api\EnquiryRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Throwable;

class Save implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly EnquiryInterfaceFactory $enquiryFactory,
        private readonly EnquiryRepositoryInterface $enquiryRepository,
        private readonly JsonFactory $resultJsonFactory,
        private readonly RequestInterface $request,
        private readonly ManagerInterface $messageManager
    ) {}

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $enquiryData = $this->request->getParam('enquiry');

            if (empty($enquiryData) || !is_array($enquiryData)) {
                throw new LocalizedException(__('Enquiry data is missing.'));
            }

            $enquiry = $this->enquiryFactory->create();

            $enquiry->setName((string)($enquiryData['name'] ?? ''));
            $enquiry->setEmail((string)($enquiryData['email'] ?? ''));
            $enquiry->setAddress((string)($enquiryData['address'] ?? ''));
            $enquiry->setSku((string)($enquiryData['sku'] ?? ''));
            $enquiry->setQty((int)($enquiryData['qty'] ?? 0));

            $this->enquiryRepository->save($enquiry);

            $this->messageManager->addSuccessMessage(
                __('Thank you! Your enquiry has been submitted.')
            );

            return $result->setData(['success' => true]);

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $result
                ->setHttpResponseCode(400)
                ->setData(['success' => false]);

        } catch (Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Error submitting enquiry. Please try again.')
            );

            return $result
                ->setHttpResponseCode(500)
                ->setData(['success' => false]);
        }
    }
}
