<?php
namespace Codilar\BlockPractice\Block;
use Magento\Framework\View\Element\Template;
class Hello extends Template
{
    public function getMessage()
    {
        return "Hello World from parimal";
    }
}
