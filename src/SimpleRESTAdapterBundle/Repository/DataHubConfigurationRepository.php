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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Repository;

use Pimcore\Bundle\DataHubBundle\Configuration;
use Pimcore\Bundle\DataHubBundle\Configuration\Dao;

final class DataHubConfigurationRepository
{
    public function findOneByName(string $name): ?Configuration
    {
        return Dao::getByName($name);
    }

    /**
     * @param array<int, string> $allowedConfigTypes
     *
     * @return Configuration[]
     */
    public function getList(array $allowedConfigTypes = []): array
    {
        $list = Dao::getList();

        if (!empty($allowedConfigTypes)) {
            $list = array_filter($list, static function ($config) use ($allowedConfigTypes) {
                return in_array($config->getType(), $allowedConfigTypes, true);
            });
        }

        return $list;
    }

    /**
     * @return bool|int
     */
    public function getModificationDate()
    {
        return Dao::getConfigModificationDate();
    }
}
