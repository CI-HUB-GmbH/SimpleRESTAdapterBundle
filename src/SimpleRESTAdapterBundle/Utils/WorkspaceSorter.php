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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Utils;

final class WorkspaceSorter
{
    public const LOWEST_SPECIFICITY = 0;
    public const HIGHEST_SPECIFICITY = 1;

    /**
     * Sorts the workspace either by lowest or highest specificity.
     *
     * @param array<int, array> $workspace
     * @param int               $sortFlag
     *
     * @return array<int, array>
     */
    public static function sort(array $workspace, int $sortFlag = self::LOWEST_SPECIFICITY): array
    {
        usort($workspace, static function ($left, $right) {
            return substr_count($left['cpath'], '/') - substr_count($right['cpath'], '/');
        });

        return self::LOWEST_SPECIFICITY === $sortFlag ? $workspace : array_reverse($workspace);
    }
}
