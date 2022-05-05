<?php
/**
 * Simple REST Adapter.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2021 CI HUB GmbH (https://ci-hub.com)
 * @license    https://github.com/ci-hub-gmbh/SimpleRESTAdapterBundle/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Provider;

use Pimcore\Model\Element\ElementInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

interface ProviderInterface
{
    /**
     * Collects all the data of an element, which then gets indexed.
     *
     * @param ElementInterface $element
     * @param ConfigReader     $reader
     *
     * @return array<string, array>
     */
    public function getIndexData(ElementInterface $element, ConfigReader $reader): array;
}
