<?php
namespace Codilar\ProductChk\Api;

interface EnquiryRepositoryInterface {
    /**
     * @param \Codilar\ProductChk\Api\Data\EnquiryInterface $enquiry
     * @return \Codilar\ProductChk\Api\Data\EnquiryInterface
     */
    public function save(\Codilar\ProductChk\Api\Data\EnquiryInterface $enquiry);

    /**
     * @return \Codilar\ProductChk\Api\Data\EnquiryInterface[]
     */
    public function getList();
}
