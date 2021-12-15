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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Loader;

use Pimcore\Bundle\DataHubBundle\Configuration;
use Webmozart\Assert\Assert;
use CIHub\Bundle\SimpleRESTAdapterBundle\Repository\DataHubConfigurationRepository;

final class CompositeConfigurationLoader
{
    /**
     * @var DataHubConfigurationRepository
     */
    private $configRepository;

    /**
     * @var iterable<ConfigurationLoaderInterface>
     */
    private $loaders;

    /**
     * @param DataHubConfigurationRepository         $configRepository
     * @param iterable<ConfigurationLoaderInterface> $loaders
     */
    public function __construct(DataHubConfigurationRepository $configRepository, iterable $loaders)
    {
        $this->configRepository = $configRepository;
        $this->loaders = $loaders;
    }

    /**
     * @return Configuration[]
     */
    public function loadConfigs(): array
    {
        return $this->configRepository->getList($this->getConfigTypes());
    }

    /**
     * @return array<int, string>
     */
    private function getConfigTypes(): array
    {
        $configTypes = [];

        foreach ($this->loaders as $loader) {
            /** @var ConfigurationLoaderInterface $loader */
            Assert::isInstanceOf($loader, ConfigurationLoaderInterface::class);

            $configTypes[] = $loader->configType();
        }

        return array_values(array_unique($configTypes));
    }
}
