<?php
/**
 * Freeform for Craft
 *
 * @package       Solspace:Freeform
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2019, Solspace, Inc.
 * @link          https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\Composer\Components\Fields\Interfaces;

use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;

interface OptionsInterface
{
    /**
     * @return Option[]
     */
    public function getOptions(): array;

    /**
     * @return array
     */
    public function getOptionsAsKeyValuePairs(): array;
}
