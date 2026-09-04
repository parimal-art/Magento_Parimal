<?php
namespace Codilar\ProductChk\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class Enquiry extends AbstractDb {
    protected function _construct() {
        $this->_init('Enquiry', 'entity_id');
    }
}
