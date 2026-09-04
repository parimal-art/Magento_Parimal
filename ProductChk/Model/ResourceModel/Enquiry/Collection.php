<?php
namespace Codilar\ProductChk\Model\ResourceModel\Enquiry;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Codilar\ProductChk\Model\Enquiry as Model;
use Codilar\ProductChk\Model\ResourceModel\Enquiry as ResourceModel;

class Collection extends AbstractCollection {
    protected function _construct() {
        $this->_init(Model::class, ResourceModel::class);
    }
}

