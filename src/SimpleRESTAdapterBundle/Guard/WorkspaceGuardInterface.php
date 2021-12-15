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
 * @copyright  Copyright (c) 2021 CI HUB GmbH (https://www.ci-hub.com)
 * @license    https://github.com/ci-hub-gmbh/SimpleRESTAdapterBundle/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Guard;

use Pimcore\Model\Element\ElementInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

interface WorkspaceGuardInterface
{
    /**
     * Checks if the element is allowed for the configured workspaces.
     *
     * @param ElementInterface $element
     * @param string           $elementType
     * @param ConfigReader     $reader
     *
     * @return bool
     */
    public function isGranted(ElementInterface $element, string $elementType, ConfigReader $reader): bool;
}
