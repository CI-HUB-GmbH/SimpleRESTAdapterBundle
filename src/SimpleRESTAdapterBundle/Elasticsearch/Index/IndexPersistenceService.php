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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Index;

use Elasticsearch\Client;
use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\DataHubBundle\Configuration;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Provider\AssetProvider;
use CIHub\Bundle\SimpleRESTAdapterBundle\Provider\DataObjectProvider;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;
use CIHub\Bundle\SimpleRESTAdapterBundle\Repository\DataHubConfigurationRepository;

final class IndexPersistenceService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var DataHubConfigurationRepository
     */
    private $configRepository;

    /**
     * @var AssetProvider
     */
    private $assetProvider;

    /**
     * @var DataObjectProvider
     */
    private $objectProvider;

    /**
     * @var array<string, string|array>
     */
    private $indexSettings;

    /**
     * @param Client                         $client
     * @param DataHubConfigurationRepository $configRepository
     * @param AssetProvider                  $assetProvider
     * @param DataObjectProvider             $objectProvider
     * @param array<string, string|array>    $indexSettings
     */
    public function __construct(
        Client $client,
        DataHubConfigurationRepository $configRepository,
        AssetProvider $assetProvider,
        DataObjectProvider $objectProvider,
        array $indexSettings
    ) {
        $this->client = $client;
        $this->configRepository = $configRepository;
        $this->assetProvider = $assetProvider;
        $this->objectProvider = $objectProvider;
        $this->indexSettings = $indexSettings;
    }

    /**
     * Checks whether the given alias name exists or not.
     *
     * @param string $name – A comma-separated list of alias names to return.
     *
     * @return bool
     */
    public function aliasExists(string $name): bool
    {
        $params = [
            'name' => $name,
        ];

        return $this->client->indices()->existsAlias($params);
    }

    /**
     * Creates or updates an index alias for the given index/indices and name.
     *
     * @param string $index – A comma-separated list of index names the alias should point to (supports wildcards);
     *                        use `_all` to perform the operation on all indices.
     * @param string $name  – The name of the alias to be created or updated.
     *
     * @return array<string, mixed>
     */
    public function createAlias(string $index, string $name): array
    {
        $params = [
            'index' => $index,
            'name' => $name,
        ];

        return $this->client->indices()->putAlias($params);
    }

    /**
     * Creates a new index either with or without settings/mappings.
     *
     * @param string               $name    – The name of the index.
     * @param array<string, mixed> $mapping – The mapping for the index.
     *
     * @return array<string, mixed>
     */
    public function createIndex(string $name, array $mapping = []): array
    {
        $params = [
            'index' => $name,
        ];

        if (!empty($mapping)) {
            $params['body'] = [
                'settings' => $this->indexSettings,
                'mappings' => $mapping,
            ];
        }

        return $this->client->indices()->create($params);
    }

    /**
     * Deletes an existing index.
     *
     * @param string $name – A comma-separated list of indices to delete;
     *                       use `_all` or `*` string to delete all indices
     *
     * @return array<string, mixed>
     */
    public function deleteIndex(string $name): array
    {
        $params = [
            'index' => $name,
        ];

        return $this->client->indices()->delete($params);
    }

    /**
     * Deletes an element from an index.
     *
     * @param int    $elementId – The ID of a Pimcore element (asset or object).
     * @param string $indexName – The name of the index to delete the item from.
     *
     * @return array<string, mixed>
     */
    public function delete(int $elementId, string $indexName): array
    {
        return $this->client->delete([
            'index' => $indexName,
            'id' => $elementId,
        ]);
    }

    /**
     * Returns all, one or filtered list of aliases.
     *
     * @param string|null $aliasName – A comma-separated list of alias names to return
     * @param string|null $indexName – A comma-separated list of index names to filter aliases
     *
     * @return array<string, mixed>
     */
    public function getAlias(string $aliasName = null, string $indexName = null): array
    {
        $params = [];

        if ($aliasName !== null) {
            $params['name'] = $aliasName;
        }

        if ($indexName !== null) {
            $params['index'] = $indexName;
        }

        return $this->client->indices()->getAlias($params);
    }

    /**
     * Returns the mapping(s) of the given index/indices.
     *
     * @param string $indexName – A comma-separated list of index names
     *
     * @return array<string, mixed>
     */
    public function getMapping(string $indexName): array
    {
        $params = [
            'index' => $indexName,
        ];

        return $this->client->indices()->getMapping($params);
    }

    /**
     * Checks whether the given index name exists or not.
     *
     * @param string $name – A comma-separated list of index names.
     *
     * @return bool
     */
    public function indexExists(string $name): bool
    {
        $params = [
            'index' => $name,
        ];

        return $this->client->indices()->exists($params);
    }

    /**
     * Refreshes one or more indices. For data streams, the API refreshes the stream’s backing indices.
     *
     * @param string $name – A comma-separated list of index names;
     *                       use `_all` or empty string to perform the operation on all indices
     *
     * @return array<string, mixed>
     */
    public function refreshIndex(string $name): array
    {
        $params = [
            'index' => $name,
        ];

        return $this->client->indices()->refresh($params);
    }

    /**
     * Reindex data from a source index to a destination index.
     *
     * @param string $source – The name of the source index.
     * @param string $dest   – The name of the destination index.
     *
     * @return array<string, mixed>
     */
    public function reindex(string $source, string $dest): array
    {
        $params = [
            'body' => [
                'source' => [
                    'index' => $source,
                ],
                'dest' => [
                    'index' => $dest,
                ],
            ],
        ];

        return $this->client->reindex($params);
    }

    /**
     * Indexes an element's data or updates the values, if it already exists.
     *
     * @param ElementInterface $element      – A Pimcore element, either asset or object.
     * @param string           $endpointName – The endpoint configuration name.
     * @param string           $indexName    – The name of the index to update the item.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function update(ElementInterface $element, string $endpointName, string $indexName): array
    {
        $configuration = $this->configRepository->findOneByName($endpointName);

        if (!$configuration instanceof Configuration) {
            throw new InvalidArgumentException(
                sprintf('No DataHub configuration found for name "%s".', $endpointName)
            );
        }

        $reader = new ConfigReader($configuration->getConfiguration());

        if ($element instanceof DataObject\AbstractObject) {
            $body = $this->objectProvider->getIndexData($element, $reader);
        } elseif ($element instanceof Asset) {
            $body = $this->assetProvider->getIndexData($element, $reader);
        } else {
            throw new InvalidArgumentException('This element type is currently not supported.');
        }

        $params = [
            'index' => $indexName,
            'id' => $element->getId(),
            'body' => $body,
        ];

        return $this->client->index($params);
    }
}
