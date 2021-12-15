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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Controller;

use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\DataHubBundle\Configuration;
use Pimcore\Bundle\DataHubBundle\Controller\ConfigController as BaseConfigController;
use Pimcore\Bundle\DataHubBundle\WorkspaceHelper;
use Pimcore\Model\Asset\Image\Thumbnail;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Extractor\LabelExtractorInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Manager\IndexManager;
use CIHub\Bundle\SimpleRESTAdapterBundle\Model\Event\ConfigurationEvent;
use CIHub\Bundle\SimpleRESTAdapterBundle\Model\Event\GetModifiedConfigurationEvent;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;
use CIHub\Bundle\SimpleRESTAdapterBundle\Repository\DataHubConfigurationRepository;
use CIHub\Bundle\SimpleRESTAdapterBundle\SimpleRESTAdapterEvents;

class ConfigController extends AdminController
{
    /**
     * @param DataHubConfigurationRepository $configRepository
     * @param EventDispatcherInterface       $eventDispatcher
     * @param Request                        $request
     *
     * @return JsonResponse
     */
    public function deleteAction(
        DataHubConfigurationRepository $configRepository,
        EventDispatcherInterface $eventDispatcher,
        Request $request
    ): JsonResponse {
        $this->checkPermission(BaseConfigController::CONFIG_NAME);

        try {
            $name = $request->get('name');
            $configuration = $configRepository->findOneByName($name);

            if (!$configuration instanceof Configuration) {
                throw new InvalidArgumentException(
                    sprintf('No DataHub configuration found for name "%s".', $name)
                );
            }

            $config = $configuration->getConfiguration();
            $preDeleteEvent = new ConfigurationEvent($config);
            $eventDispatcher->dispatch($preDeleteEvent, SimpleRESTAdapterEvents::CONFIGURATION_PRE_DELETE);

            WorkspaceHelper::deleteConfiguration($configuration);
            $configuration->delete();

            $postDeleteEvent = new ConfigurationEvent($config);
            $eventDispatcher->dispatch($postDeleteEvent, SimpleRESTAdapterEvents::CONFIGURATION_POST_DELETE);

            return $this->json(['success' => true]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param DataHubConfigurationRepository $configRepository
     * @param Request                        $request
     *
     * @return JsonResponse
     */
    public function getAction(DataHubConfigurationRepository $configRepository, Request $request): JsonResponse
    {
        $this->checkPermission(BaseConfigController::CONFIG_NAME);

        $configName = $request->get('name');
        $configuration = $configRepository->findOneByName($configName);

        if (!$configuration instanceof Configuration) {
            throw new InvalidArgumentException(
                sprintf('No DataHub configuration found for name "%s".', $configName)
            );
        }

        // Add endpoint routes to current config
        $reader = new ConfigReader($configuration->getConfiguration());
        $reader->add([
            'swaggerUrl' => $this->getEndpoint('simple_rest_adapter_swagger_ui'),
            'treeItemsUrl' => $this->getEndpoint('simple_rest_adapter_endpoints_tree_items', ['config' => $configName]),
            'searchUrl' => $this->getEndpoint('simple_rest_adapter_endpoints_get_element', ['config' => $configName]),
            'getElementByIdUrl' => $this->getEndpoint('simple_rest_adapter_endpoints_get_element', ['config' => $configName]),
        ]);

        return $this->json([
            'name' => $configName,
            'configuration' => $reader->toArray(),
            'modificationDate' => $configRepository->getModificationDate(),
        ]);
    }

    /**
     * @param DataHubConfigurationRepository $configRepository
     * @param IndexManager                   $indexManager
     * @param LabelExtractorInterface        $labelExtractor
     * @param Request                        $request
     *
     * @return JsonResponse
     */
    public function labelListAction(
        DataHubConfigurationRepository $configRepository,
        IndexManager $indexManager,
        LabelExtractorInterface $labelExtractor,
        Request $request
    ): JsonResponse {
        $this->checkPermission(BaseConfigController::CONFIG_NAME);

        $configName = $request->get('name');
        $configuration = $configRepository->findOneByName($configName);

        if (!$configuration instanceof Configuration) {
            throw new InvalidArgumentException(
                sprintf('No DataHub configuration found for name "%s".', $configName)
            );
        }

        $reader = new ConfigReader($configuration->getConfiguration());
        $indices = array_merge(
            [$indexManager->getIndexName(IndexManager::INDEX_ASSET, $configName)],
            array_map(static function ($className) use ($indexManager, $configName) {
                return $indexManager->getIndexName(strtolower($className), $configName);
            }, $reader->getObjectClassNames())
        );

        $labels = $labelExtractor->extractLabels($indices);

        return $this->json(['success' => true, 'labelList' => $labels]);
    }

    /**
     * @param DataHubConfigurationRepository $configRepository
     * @param EventDispatcherInterface       $eventDispatcher
     * @param Request                        $request
     *
     * @return JsonResponse
     */
    public function saveAction(
        DataHubConfigurationRepository $configRepository,
        EventDispatcherInterface $eventDispatcher,
        Request $request
    ): JsonResponse {
        $this->checkPermission(BaseConfigController::CONFIG_NAME);

        try {
            $data = $request->get('data');
            $modificationDate = $request->get('modificationDate', 0);
            $newConfigReader = new ConfigReader(json_decode($data, true));

            $name = $newConfigReader->getName();
            $configuration = $configRepository->findOneByName($name);

            if (!$configuration instanceof Configuration) {
                throw new InvalidArgumentException(
                    sprintf('No DataHub configuration found for name "%s".', $name)
                );
            }

            $reader = new ConfigReader($configuration->getConfiguration());
            $savedModificationDate = $reader->getModificationDate();

            // ToDo Fix modifcationDate
//            if ($modificationDate < $savedModificationDate) {
//                throw new RuntimeException('The configuration was modified during editing, please reload the configuration and make your changes again.');
//            }

            $oldConfig = $reader->toArray();
            $newConfig = $newConfigReader->toArray();
            $newConfig['general']['modificationDate'] = time();

            $preSaveEvent = new GetModifiedConfigurationEvent($newConfig, $oldConfig);

            $eventDispatcher->dispatch($preSaveEvent, SimpleRESTAdapterEvents::CONFIGURATION_PRE_SAVE);

            $newConfig = $preSaveEvent->getModifiedConfiguration() ?? $newConfig;

            $configuration->setConfiguration($newConfig);
            $configuration->save();

            $postSaveEvent = new ConfigurationEvent($newConfig, $oldConfig);
            $eventDispatcher->dispatch($postSaveEvent, SimpleRESTAdapterEvents::CONFIGURATION_POST_SAVE);

            return $this->json(['success' => true, 'modificationDate' => $configRepository->getModificationDate()]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @return JsonResponse
     */
    public function thumbnailsAction(): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $configList = new Thumbnail\Config\Listing();
        $thumbnails = array_map(
            static function ($config) {
                return ['name' => $config->getName()];
            },
            $configList->load()
        );

        return $this->json(['data' => $thumbnails]);
    }

    /**
     * @param string                $route
     * @param array<string, string> $parameters
     *
     * @return string
     */
    private function getEndpoint(string $route, array $parameters = []): string
    {
        return $this->generateUrl($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
