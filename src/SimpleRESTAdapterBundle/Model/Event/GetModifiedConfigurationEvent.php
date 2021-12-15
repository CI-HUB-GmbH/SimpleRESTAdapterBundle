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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Model\Event;

class GetModifiedConfigurationEvent extends ConfigurationEvent
{
    /**
     * @var array<string, array>|null
     */
    private $modifiedConfiguration;

    /**
     * @return array<string, array>|null
     */
    public function getModifiedConfiguration(): ?array
    {
        return $this->modifiedConfiguration;
    }

    /**
     * @param array<string, array> $modifiedConfiguration
     */
    public function setModifiedConfiguration(array $modifiedConfiguration): void
    {
        $this->modifiedConfiguration = $modifiedConfiguration;
    }
}
