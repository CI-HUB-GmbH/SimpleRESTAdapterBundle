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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\EventListener;

use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Mapping\AssetMapping;
use CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Mapping\DataObjectMapping;
use CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Mapping\FolderMapping;
use CIHub\Bundle\SimpleRESTAdapterBundle\Exception\ESClientException;
use CIHub\Bundle\SimpleRESTAdapterBundle\Manager\IndexManager;
use CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\InitializeEndpointMessage;
use CIHub\Bundle\SimpleRESTAdapterBundle\Model\Event\ConfigurationEvent;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;
use CIHub\Bundle\SimpleRESTAdapterBundle\SimpleRESTAdapterEvents;

class ConfigModificationListener implements EventSubscriberInterface
{
    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var AssetMapping
     */
    private $assetMapping;

    /**
     * @var DataObjectMapping
     */
    private $objectMapping;

    /**
     * @var FolderMapping
     */
    private $folderMapping;

    /**
     * @param IndexManager        $indexManager
     * @param MessageBusInterface $messageBus
     * @param AssetMapping        $assetMapping
     * @param DataObjectMapping   $objectMapping
     * @param FolderMapping       $folderMapping
     */
    public function __construct(
        IndexManager $indexManager,
        MessageBusInterface $messageBus,
        AssetMapping $assetMapping,
        DataObjectMapping $objectMapping,
        FolderMapping $folderMapping
    ) {
        $this->indexManager = $indexManager;
        $this->messageBus = $messageBus;
        $this->assetMapping = $assetMapping;
        $this->objectMapping = $objectMapping;
        $this->folderMapping = $folderMapping;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SimpleRESTAdapterEvents::CONFIGURATION_PRE_DELETE => 'onPreDelete',
            SimpleRESTAdapterEvents::CONFIGURATION_POST_SAVE => 'onPostSave',
        ];
    }

    /**
     * @param ConfigurationEvent $event
     */
    public function onPreDelete(ConfigurationEvent $event): void
    {
        $reader = new ConfigReader($event->getConfiguration());
        $this->indexManager->deleteAllIndices($reader->getName());
    }

    /**
     * @param ConfigurationEvent $event
     *
     * @throws RuntimeException
     * @throws ESClientException
     */
    public function onPostSave(ConfigurationEvent $event): void
    {
        $reader = new ConfigReader($event->getConfiguration());
        $priorReader = new ConfigReader($event->getPriorConfiguration());

        // Handle asset indices
        if ($reader->isAssetIndexingEnabled()) {
            $this->handleAssetIndices($reader);
        }

        // Handle object indices
        if ($reader->isObjectIndexingEnabled()) {
            $this->handleObjectIndices($reader);
        }

        // Initialize endpoint
        if ($this->indexManager->hasWorkspaceChanged($reader, $priorReader)) {
            $this->initializeEndpoint($reader);
        }
    }

    /**
     * @param ConfigReader $reader
     */
    private function handleAssetIndices(ConfigReader $reader): void
    {
        $endpointName = $reader->getName();

        // Asset Folders
        $this->indexManager->createOrUpdateIndex(
            $this->indexManager->getIndexName(IndexManager::INDEX_ASSET_FOLDER, $endpointName),
            $this->folderMapping->generate()
        );

        // Assets
        $this->indexManager->createOrUpdateIndex(
            $this->indexManager->getIndexName(IndexManager::INDEX_ASSET, $endpointName),
            $this->assetMapping->generate($reader->toArray())
        );
    }

    /**
     * @param ConfigReader $reader
     */
    private function handleObjectIndices(ConfigReader $reader): void
    {
        $endpointName = $reader->getName();

        // DataObject Folders
        $this->indexManager->createOrUpdateIndex(
            $this->indexManager->getIndexName(IndexManager::INDEX_OBJECT_FOLDER, $endpointName),
            $this->folderMapping->generate()
        );

        $objectClasses = $reader->getObjectClasses();

        // DataObject Classes
        foreach ($objectClasses as $class) {
            $this->indexManager->createOrUpdateIndex(
                $this->indexManager->getIndexName(strtolower($class['name']), $endpointName),
                $this->objectMapping->generate($class)
            );
        }
    }

    /**
     * @param ConfigReader $reader
     *
     * @throws RuntimeException
     * @throws ESClientException
     */
    private function initializeEndpoint(ConfigReader $reader): void
    {
        $indices = $this->indexManager->getAllIndexNames($reader);

        // Clear index data
        foreach ($indices as $index) {
            $this->indexManager->clearIndexData($index);
        }

        $this->messageBus->dispatch(new InitializeEndpointMessage($reader->getName()));
    }
}
