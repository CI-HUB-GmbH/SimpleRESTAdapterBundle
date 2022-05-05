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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Guard;

use Pimcore\Model\Element\ElementInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;
use CIHub\Bundle\SimpleRESTAdapterBundle\Utils\WorkspaceSorter;

final class WorkspaceGuard implements WorkspaceGuardInterface
{
    /**
     * {@inheritdoc}
     */
    public function isGranted(ElementInterface $element, string $elementType, ConfigReader $reader): bool
    {
        $workspace = WorkspaceSorter::sort($reader->getWorkspace($elementType), WorkspaceSorter::HIGHEST_SPECIFICITY);

        // No workspace configuration found for element type
        if (empty($workspace)) {
            return false;
        }

        foreach ($workspace as $config) {
            // Check if element is within folder
            if (false === strpos($element->getFullPath(), $config['cpath'])) {
                continue;
            }

            return $config['read'];
        }

        return false;
    }
}
