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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SimpleRESTAdapterExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @var array
     */
    private $ciHubConfig = [];

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['CIHubAdapterBundle'])) {
            $this->ciHubConfig = $container->getExtensionConfig('ci_hub_adapter');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerConfiguration($container, $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Registers the configuration as parameters to the container.
     *
     * @param ContainerBuilder            $container
     * @param array<string, string|array> $config
     */
    private function registerConfiguration(ContainerBuilder $container, array $config): void
    {
        if (!empty($this->ciHubConfig)) {
            $config = array_merge($config, ...$this->ciHubConfig);
        }

        $container->setParameter('simple_rest_adapter.index_name_prefix', $config['index_name_prefix']);
        $container->setParameter('simple_rest_adapter.es_hosts', $config['es_hosts']);
        $container->setParameter('simple_rest_adapter.index_settings', $config['index_settings']);
        $container->setParameter('simple_rest_adapter.default_preview_thumbnail', $config['default_preview_thumbnail'] ?? []);
    }
}
