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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Manager;

use InvalidArgumentException;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use RuntimeException;
use CIHub\Bundle\SimpleRESTAdapterBundle\Exception\ESClientException;
use CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Index\IndexPersistenceService;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;
use CIHub\Bundle\SimpleRESTAdapterBundle\Utils\DiffArray;

final class IndexManager
{
    public const INDEX_ASSET = 'asset';
    public const INDEX_ASSET_FOLDER = 'assetfolder';
    public const INDEX_OBJECT_FOLDER = 'objectfolder';

    /**
     * @var string
     */
    private $indexNamePrefix;

    /**
     * @var IndexPersistenceService
     */
    private $indexService;

    /**
     * @param string                  $indexNamePrefix
     * @param IndexPersistenceService $indexService
     */
    public function __construct(string $indexNamePrefix, IndexPersistenceService $indexService)
    {
        $this->indexNamePrefix = $indexNamePrefix;
        $this->indexService = $indexService;
    }

    /**
     * Clears an index by cloning and renaming the existing index.
     *
     * @param string $indexName
     *
     * @throws RuntimeException
     * @throws ESClientException
     */
    public function clearIndexData(string $indexName): void
    {
        $mapping = $this->getIndexMapping($indexName);

        if (empty($mapping)) {
            throw new RuntimeException(
                sprintf('Could not clear index data. No mapping found for "%s"', $indexName)
            );
        }

        $this->updateMapping($indexName, $mapping, false, true);
    }

    /**
     * Creates or updates an index by its name and mapping.
     *
     * @param string                $indexName – The index to create or update.
     * @param array<string, mixed>  $mapping   – The mapping to check against.
     */
    public function createOrUpdateIndex(string $indexName, array $mapping): void
    {
        if ($this->indexService->aliasExists($indexName)) {
            $this->updateMapping($indexName, $mapping);
        } else {
            $evenIndexName = sprintf('%s-even', $indexName);
            $this->indexService->createIndex($evenIndexName, $mapping);
            $this->indexService->createAlias($evenIndexName, $indexName);
        }
    }

    /**
     * Deletes all indices of a certain endpoint configuration.
     *
     * @param string $endpointName
     */
    public function deleteAllIndices(string $endpointName): void
    {
        $endpointIndices = sprintf('%s__%s*', $this->indexNamePrefix, $endpointName);
        $this->indexService->deleteIndex($endpointIndices);
    }

    /**
     * Tries to find an index by alias name.
     *
     * @param string $aliasName – The name of the alias of an index.
     *
     * @return string
     */
    public function findIndexNameByAlias(string $aliasName): string
    {
        $aliases = $this->indexService->getAlias($aliasName);

        foreach ($aliases as $index => $aliasMapping) {
            if (array_key_exists($aliasName, $aliasMapping['aliases'])) {
                return $index;
            }
        }

        return '';
    }

    /**
     * Returns all index names of the current
     *
     * @param ConfigReader $reader
     *
     * @return array<int, string>
     */
    public function getAllIndexNames(ConfigReader $reader): array
    {
        $endpointName = $reader->getName();

        return array_merge(
            [
                $this->getIndexName(self::INDEX_ASSET, $endpointName),
                $this->getIndexName(self::INDEX_ASSET_FOLDER, $endpointName),
                $this->getIndexName(self::INDEX_OBJECT_FOLDER, $endpointName),
            ],
            array_map(function ($className) use ($endpointName) {
                return $this->getIndexName(strtolower($className), $endpointName);
            }, $reader->getObjectClassNames())
        );
    }

    /**
     * Returns only the mapping of the provided index name.
     *
     * @param string $indexName – A comma-separated list of index names
     *
     * @return array<string, mixed>
     */
    public function getIndexMapping(string $indexName): array
    {
        if (!str_ends_with($indexName, '-odd') && !str_ends_with($indexName, '-even')) {
            if (!$this->indexService->aliasExists($indexName)) {
                throw new RuntimeException(
                    sprintf('Could not get index mapping. No alias found for "%s"', $indexName)
                );
            }

            $indexName = $this->findIndexNameByAlias($indexName);
        }

        $indexMapping = $this->indexService->getMapping($indexName);

        return $indexMapping[$indexName]['mappings'] ?? [];
    }

    /**
     * Builds the index name for a given name or element and the endpoint's name.
     *
     * @param mixed  $value        – A string or a Pimcore element.
     * @param string $endpointName – The endpoint's name.
     *
     * @return string
     */
    public function getIndexName($value, string $endpointName): string
    {
        $indexName = $value;

        if ($value instanceof ElementInterface) {
            if ($value instanceof Asset\Folder) {
                $indexName = self::INDEX_ASSET_FOLDER;
            } elseif ($value instanceof Asset) {
                $indexName = self::INDEX_ASSET;
            } elseif ($value instanceof DataObject\Folder) {
                $indexName = self::INDEX_OBJECT_FOLDER;
            } elseif ($value instanceof DataObject\Concrete) {
                $indexName = strtolower($value->getClassName());
            }
        }

        if (!is_string($indexName)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The given value must either be a string or a Pimcore element, %s given.',
                    gettype($value)
                )
            );
        }

        return sprintf(
            '%s__%s__%s',
            $this->indexNamePrefix,
            $endpointName,
            $indexName
        );
    }

    /**
     * Checks whether the current mapping of an index is different to the given mapping.
     *
     * @param string                $indexName
     * @param array<string, mixed>  $mapping
     *
     * @return bool
     */
    public function hasMappingChanged(string $indexName, array $mapping): bool
    {
        $currentMapping = $this->getIndexMapping($indexName);

        return !empty(array_merge(
            DiffArray::diffAssocRecursive($mapping, $currentMapping),
            DiffArray::diffAssocRecursive($currentMapping, $mapping)
        ));
    }

    /**
     * Checks whether the workspaces have changed compared to the prior configuration or not.
     *
     * @param ConfigReader $reader
     * @param ConfigReader $priorReader
     *
     * @return bool
     */
    public function hasWorkspaceChanged(ConfigReader $reader, ConfigReader $priorReader): bool
    {
        $assetWorkspace = $reader->getWorkspace('asset');
        $priorAssetWorkspace = $priorReader->getWorkspace('asset');

        if (!empty(DiffArray::diffAssocRecursive($assetWorkspace, $priorAssetWorkspace))) {
            return true;
        }

        $objectWorkspace = $reader->getWorkspace('object');
        $priorObjectWorkspace = $priorReader->getWorkspace('object');

        if (!empty(DiffArray::diffAssocRecursive($objectWorkspace, $priorObjectWorkspace))) {
            return true;
        }

        return false;
    }

    /**
     * Creates a new target index with mappings and re-indexes the data from the source index.
     * Then a new alias pointing to the new index is created and the old index is removed.
     *
     * @param string                $indexName
     * @param array<string, mixed>  $mapping
     * @param bool                  $reindexData
     * @param bool                  $force
     *
     * @throws ESClientException
     */
    public function updateMapping(string $indexName, array $mapping, bool $reindexData = true, bool $force = false): void
    {
        $oddIndexName = sprintf('%s-odd', $indexName);
        $evenIndexName = sprintf('%s-even', $indexName);

        if ($this->indexService->indexExists($oddIndexName)) {
            $source = $oddIndexName;
            $target = $evenIndexName;
        } else {
            $source = $evenIndexName;
            $target = $oddIndexName;
        }

        if ($force || $this->hasMappingChanged($source, $mapping)) {
            // Create a new target index with mapping
            $indexResponse = $this->indexService->createIndex($target, $mapping);
            if (!isset($indexResponse['acknowledged']) || $indexResponse['acknowledged'] !== true) {
                throw new ESClientException(sprintf('Could not create index "%s"', $target));
            }

            if ($reindexData) {
                // Refresh source index before re-indexing the data
                $refreshResponse = $this->indexService->refreshIndex($source);
                if (!isset($refreshResponse['_shards']['failed']) || $refreshResponse['_shards']['failed'] > 0) {
                    throw new ESClientException(sprintf('Could not refresh index "%s"', $source));
                }

                // Reindex the data from the source to the target index
                $reIndexResponse = $this->indexService->reindex($source, $target);
                if (!isset($reIndexResponse['failures']) || !empty($reIndexResponse['failures'])) {
                    throw new ESClientException(
                        sprintf('Could not reindex data from "%s" to "%s"', $source, $target)
                    );
                }
            }

            // Create the alias for the new target index
            $aliasResponse = $this->indexService->createAlias($target, $indexName);
            if (!isset($aliasResponse['acknowledged']) || $aliasResponse['acknowledged'] !== true) {
                throw new ESClientException(sprintf('Could not create alias for "%s"', $target));
            }

            // Delete the old source index
            $this->indexService->deleteIndex($source);
        }
    }
}
