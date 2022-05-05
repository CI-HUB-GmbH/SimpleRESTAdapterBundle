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
use Elasticsearch\Common\Exceptions\Missing404Exception;
use ONGR\ElasticsearchDSL\Search;

final class IndexQueryService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $indexNamePrefix;

    /**
     * @param Client $client
     * @param string $indexNamePrefix
     */
    public function __construct(Client $client, string $indexNamePrefix)
    {
        $this->client = $client;
        $this->indexNamePrefix = $indexNamePrefix;
    }

    /**
     * @return Search
     */
    public function createSearch(): Search
    {
        return new Search();
    }

    /**
     * @param int    $id
     * @param string $index
     *
     * @return array<string, mixed>
     *
     * @throws Missing404Exception
     */
    public function get(int $id, string $index): array
    {
        $params = [
            'id' => $id,
            'index' => $index,
        ];

        return $this->client->get($params);
    }

    /**
     * @param string               $index
     * @param array<string, mixed> $query
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function search(string $index, array $query, array $params = []): array
    {
        if (str_ends_with($index, '*')) {
            $index = sprintf('%s__%s', $this->indexNamePrefix, ltrim($index, '_'));
        }

        $requestParams = [
            'index' => $index,
            'body' => $query,
        ];

        if (!empty($params)) {
            $requestParams = array_merge($requestParams, $params);
        }

        return $this->client->search($requestParams);
    }
}
