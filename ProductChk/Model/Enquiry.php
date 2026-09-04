<?php
namespace Codilar\ProductChk\Model;

use Magento\Framework\Model\AbstractModel;
use Codilar\ProductChk\Api\Data\EnquiryInterface;

class Enquiry extends AbstractModel implements EnquiryInterface {
    protected function _construct() {
        $this->_init(\Codilar\ProductChk\Model\ResourceModel\Enquiry::class);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getId() { return $this->getData(self::ENTITY_ID); }

    /**
     * @param $id
     * @return Enquiry
     */
    public function setId($id) { return $this->setData(self::ENTITY_ID, $id); }

    /**
     * @return array|mixed|string|null
     */
    public function getName() { return $this->getData(self::NAME); }

    /**
     * @param $name
     * @return Enquiry
     */
    public function setName($name) { return $this->setData(self::NAME, $name); }

    /**
     * @return array|mixed|string|null
     */
    public function getEmail() { return $this->getData(self::EMAIL); }

    /**
     * @param $email
     * @return Enquiry
     */
    public function setEmail($email) { return $this->setData(self::EMAIL, $email); }

    /**
     * @return array|mixed|string|null
     */
    public function getAddress() { return $this->getData(self::ADDRESS); }

    /**
     * @param $address
     * @return Enquiry
     */
    public function setAddress($address) { return $this->setData(self::ADDRESS, $address); }

    /**
     * @return array|mixed|string|null
     */
    public function getSku() { return $this->getData(self::SKU); }

    /**
     * @param $sku
     * @return Enquiry
     */
    public function setSku($sku) { return $this->setData(self::SKU, $sku); }

    /**
     * @return array|int|mixed|null
     */
    public function getQty() { return $this->getData(self::QTY); }

    /**
     * @param $qty
     * @return Enquiry
     */
    public function setQty($qty) { return $this->setData(self::QTY, $qty); }
}
