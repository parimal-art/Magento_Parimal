<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * Renders the dynamic rows grid for managing subscription frequencies.
 */
class Frequencies extends AbstractFieldArray
{
    /**
     * Prepare rendering the new field by adding all the needed columns.
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn(
            'code',
            [
                'label' => __('Code'),
                'class' => 'required-entry',
                'style' => 'width:150px;'
            ]
        );

        $this->addColumn(
            'label',
            [
                'label' => __('Label'),
                'class' => 'required-entry',
                'style' => 'width:200px;'
            ]
        );

        $this->addColumn(
            'modifier',
            [
                'label' => __('Modifier (e.g. +7 days)'),
                'class' => 'required-entry',
                'style' => 'width:150px;'
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Frequency');
    }

    /**
     * Prepare existing row data object.
     *
     * This is required to ensure existing saved data is populated correctly.
     *
     * @param DataObject $row
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        // No special preparation needed for simple text columns.
    }
}
