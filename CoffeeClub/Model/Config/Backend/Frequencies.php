<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * Backend model for the "frequencies" dynamic rows field.
 *
 * It extends ArraySerialized to automatically serialize the array of rows
 * before saving to the database, and unserialize it when loading.
 */
class Frequencies extends ArraySerialized
{
    // No custom logic needed. The parent class handles serialization.
}
