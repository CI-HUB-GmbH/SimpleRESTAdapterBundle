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

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Guard\WorkspaceGuardInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Loader\CompositeConfigurationLoader;
use CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\DeleteIndexElementMessage;
use CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\UpdateIndexElementMessage;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

class ElementEnqueueingListener implements EventSubscriberInterface
{
    /**
     * @var CompositeConfigurationLoader
     */
    private $configLoader;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var WorkspaceGuardInterface
     */
    private $workspaceGuard;

    /**
     * @param CompositeConfigurationLoader $configLoader
     * @param MessageBusInterface          $messageBus
     * @param WorkspaceGuardInterface      $workspaceGuard
     */
    public function __construct(
        CompositeConfigurationLoader $configLoader,
        MessageBusInterface $messageBus,
        WorkspaceGuardInterface $workspaceGuard
    ) {
        $this->configLoader = $configLoader;
        $this->messageBus = $messageBus;
        $this->workspaceGuard = $workspaceGuard;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::POST_ADD => 'enqueueAsset',
            AssetEvents::POST_UPDATE => 'enqueueAsset',
            AssetEvents::POST_DELETE => 'removeAsset',
            DataObjectEvents::POST_ADD => 'enqueueObject',
            DataObjectEvents::POST_UPDATE => 'enqueueObject',
            DataObjectEvents::POST_DELETE => 'removeObject',
        ];
    }

    /**
     * @param AssetEvent $event
     */
    public function enqueueAsset(AssetEvent $event): void
    {
        $type = 'asset';
        $asset = $event->getAsset();

        if (!$asset instanceof Asset) {
            return;
        }

        $configurations = $this->configLoader->loadConfigs();

        foreach ($configurations as $configuration) {
            $name = $configuration->getName();
            $reader = new ConfigReader($configuration->getConfiguration());

            // Check if assets are enabled
            if (!$reader->isAssetIndexingEnabled()) {
                continue;
            }

            if ($this->workspaceGuard->isGranted($asset, $type, $reader)) {
                $this->messageBus->dispatch(new UpdateIndexElementMessage($asset->getId(), $type, $name));

                // Index all folders above the asset
                $this->enqueueParentFolders($asset->getParent(), Asset\Folder::class, $type, $name);
            } else {
                $this->messageBus->dispatch(new DeleteIndexElementMessage($asset->getId(), $type, $name));
            }
        }
    }

    /**
     * @param DataObjectEvent $event
     */
    public function enqueueObject(DataObjectEvent $event): void
    {
        $type = 'object';
        $object = $event->getObject();

        if (!$object instanceof DataObject\Concrete) {
            return;
        }

        $configurations = $this->configLoader->loadConfigs();

        foreach ($configurations as $configuration) {
            $name = $configuration->getName();
            $reader = new ConfigReader($configuration->getConfiguration());
            $objectClassNames = $reader->getObjectClassNames();

            // Check if object class is configured
            if (!in_array($object->getClassName(), $objectClassNames, true)) {
                continue;
            }

            if ($this->workspaceGuard->isGranted($object, $type, $reader)) {
                $this->messageBus->dispatch(new UpdateIndexElementMessage($object->getId(), $type, $name));

                // Index all folders above the object
                $this->enqueueParentFolders($object->getParent(), DataObject\Folder::class, $type, $name);
            } else {
                $this->messageBus->dispatch(new DeleteIndexElementMessage($object->getId(), $type, $name));
            }
        }
    }

    /**
     * @param AssetEvent $event
     */
    public function removeAsset(AssetEvent $event): void
    {
        $type = 'asset';
        $asset = $event->getAsset();

        if (!$asset instanceof Asset) {
            return;
        }

        $configurations = $this->configLoader->loadConfigs();

        foreach ($configurations as $configuration) {
            $name = $configuration->getName();
            $reader = new ConfigReader($configuration->getConfiguration());

            // Check if assets are enabled
            if (!$reader->isAssetIndexingEnabled()) {
                continue;
            }

            if ($this->workspaceGuard->isGranted($asset, $type, $reader)) {
                $this->messageBus->dispatch(new DeleteIndexElementMessage($asset->getId(), $type, $name));
            }
        }
    }

    /**
     * @param DataObjectEvent $event
     */
    public function removeObject(DataObjectEvent $event): void
    {
        $type = 'object';
        $object = $event->getObject();

        if (!$object instanceof DataObject\Concrete) {
            return;
        }

        $configurations = $this->configLoader->loadConfigs();

        foreach ($configurations as $configuration) {
            $name = $configuration->getName();
            $reader = new ConfigReader($configuration->getConfiguration());
            $objectClassNames = $reader->getObjectClassNames();

            // Check if object class is configured
            if (!in_array($object->getClassName(), $objectClassNames, true)) {
                continue;
            }

            if ($this->workspaceGuard->isGranted($object, $type, $reader)) {
                $this->messageBus->dispatch(new DeleteIndexElementMessage($object->getId(), $type, $name));
            }
        }
    }

    /**
     * @param ElementInterface|null $parent
     * @param string                $folderClass
     * @param string                $type
     * @param string                $name
     */
    private function enqueueParentFolders(
        ?ElementInterface $parent,
        string $folderClass,
        string $type,
        string $name
    ): void {
        while ($parent instanceof $folderClass && $parent->getId() !== 1) {
            $this->messageBus->dispatch(new UpdateIndexElementMessage($parent->getId(), $type, $name));
            $parent = $parent->getParent();
        }
    }
}
