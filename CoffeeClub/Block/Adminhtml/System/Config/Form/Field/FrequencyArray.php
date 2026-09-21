<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Renders the "Available Frequencies" admin field as a dynamic row grid.
 *
 * Each row has three columns: code, label, modifier.
 * Values are persisted as a JSON array by ArraySerialized backend model.
 *
 * Backwards compatible with the previous textarea format:
 * "code:Label:+N unit" one per line — auto-converted to rows on first load.
 */
class FrequencyArray extends AbstractFieldArray
{
    /**
     * Define the columns and the "Add Row" button.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('code', [
            'label' => __('Code'),
            'class' => 'required-entry admin__control-text',
            'style' => 'width: 140px;',
        ]);

        $this->addColumn('label', [
            'label' => __('Label'),
            'class' => 'required-entry admin__control-text',
            'style' => 'width: 220px;',
        ]);

        $this->addColumn('modifier', [
            'label' => __('Date Modifier'),
            'class' => 'required-entry admin__control-text',
            'style' => 'width: 170px;',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Frequency');
    }

    /**
     * Decode the stored config value into rows.
     *
     * Handles (in order of priority):
     *   1. Already an array (Magento may pass this in some flows)
     *   2. JSON  — new format written by the ArraySerialized backend model
     *   3. PHP-serialized array — fallback for older Magento serialization
     *   4. Legacy plain-text "code:Label:+N unit" one per line
     *
     * @return array
     */
    protected function _getValue()
    {
        $raw = $this->getElement()->getValue();

        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        // 1. JSON
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // 2. PHP serialized
        if (preg_match('/^a:\d+:{/', $raw)) {
            $unserialized = @unserialize($raw, ['allowed_classes' => false]);
            if (is_array($unserialized)) {
                return $unserialized;
            }
        }

        // 3. Legacy textarea: "code:Label:+N unit" per line
        if (strpos($raw, ':') !== false) {
            $rows = [];
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode(':', $line, 3));
                if (count($parts) !== 3) {
                    continue;
                }
                [$code, $label, $modifier] = $parts;
                if ($code === '' || $modifier === '') {
                    continue;
                }
                $rows[] = [
                    'code' => $code,
                    'label' => $label,
                    'modifier' => $modifier,
                ];
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        return [];
    }
}
