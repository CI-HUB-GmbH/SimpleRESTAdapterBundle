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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\Handler;

use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Pimcore\Bundle\DataHubBundle\Configuration;
use Pimcore\Db\ConnectionInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\InitializeEndpointMessage;
use CIHub\Bundle\SimpleRESTAdapterBundle\Messenger\UpdateIndexElementMessage;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;
use CIHub\Bundle\SimpleRESTAdapterBundle\Repository\DataHubConfigurationRepository;
use CIHub\Bundle\SimpleRESTAdapterBundle\Utils\WorkspaceSorter;

final class InitializeEndpointMessageHandler implements MessageHandlerInterface
{
    private const CONDITION_DISTINCT = 'distinct';
    private const CONDITION_INCLUSIVE = 'inclusive';
    private const CONDITION_EXCLUSIVE = 'exclusive';

    /**
     * @var array<string, array>
     */
    private $conditions = [];

    /**
     * @var DataHubConfigurationRepository
     */
    private $configRepository;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var array<string, string>
     */
    private $params = [];

    /**
     * @param DataHubConfigurationRepository $configRepository
     * @param ConnectionInterface            $connection
     * @param MessageBusInterface            $messageBus
     */
    public function __construct(
        DataHubConfigurationRepository $configRepository,
        ConnectionInterface $connection,
        MessageBusInterface $messageBus
    ) {
        $this->configRepository = $configRepository;
        $this->connection = $connection;
        $this->messageBus = $messageBus;
    }

    /**
     * @param InitializeEndpointMessage $message
     */
    public function __invoke(InitializeEndpointMessage $message): void
    {
        $endpointName = $message->getEndpointName();
        $configuration = $this->configRepository->findOneByName($endpointName);

        if (!$configuration instanceof Configuration) {
            return;
        }

        $reader = new ConfigReader($configuration->getConfiguration());

        // Initialize assets
        if ($reader->isAssetIndexingEnabled()) {
            $workspace = WorkspaceSorter::sort($reader->getWorkspace('asset'));
            $this->buildConditions($workspace, 'filename', 'path');

            if (isset($this->conditions[self::CONDITION_INCLUSIVE]) && !empty($this->params)) {
                $ids = $this->fetchIdsFromDatabaseTable('assets', 'id');

                foreach ($ids as $id) {
                    $this->messageBus->dispatch(
                        new UpdateIndexElementMessage($id, 'asset', $endpointName)
                    );
                }
            }

            // Reset conditions and params
            $this->conditions = $this->params = [];
        }

        // Initialize objects
        if ($reader->isObjectIndexingEnabled()) {
            $workspace = WorkspaceSorter::sort($reader->getWorkspace('object'));
            $this->buildConditions($workspace, 'o_key', 'o_path');

            if (isset($this->conditions[self::CONDITION_INCLUSIVE]) && !empty($this->params)) {
                $ids = $this->fetchIdsFromDatabaseTable('objects', 'o_id');

                foreach ($ids as $id) {
                    $this->messageBus->dispatch(
                        new UpdateIndexElementMessage($id, 'object', $endpointName)
                    );
                }
            }
        }
    }

    /**
     * Builds the conditions for database query.
     *
     * @param array<int, array> $workspace
     * @param string            $keyColumn
     * @param string            $pathColumn
     */
    private function buildConditions(array $workspace, string $keyColumn, string $pathColumn): void
    {
        if (empty($workspace)) {
            return;
        }

        foreach ($workspace as $item) {
            $read = $item['read'];
            $path = $item['cpath'];
            $pathParts = explode('/', $path);

            // If not root folder, add distinct conditions
            if (count($pathParts) > 2 || $pathParts[1] !== '') {
                $this->addDistinctConditions($pathParts, $keyColumn, $pathColumn);
            }

            // Always add the ex-/inclusive conditions
            $pathIndex = uniqid('path_', false);
            $this->conditions[$read ? self::CONDITION_INCLUSIVE : self::CONDITION_EXCLUSIVE][] = sprintf(
                '%s %s :%s',
                $pathColumn,
                $read ? 'LIKE' : 'NOT LIKE',
                $pathIndex
            );
            $this->params[$pathIndex] = rtrim($path, '/') . '/%';
        }
    }

    /**
     * Builds the conditions for distinct elements.
     *
     * @param array<int, string>  $pathParts
     * @param string              $keyColumn
     * @param string              $pathColumn
     */
    private function addDistinctConditions(array $pathParts, string $keyColumn, string $pathColumn): void
    {
        $keyIndex = uniqid('key_', false);
        $keyPathIndex = uniqid('key_path_', false);
        $keyParam = array_pop($pathParts);
        $keyPathParam = implode('/', $pathParts) . '/';

        if (!in_array($keyParam, $this->params, true)) {
            $this->conditions[self::CONDITION_DISTINCT][] = sprintf(
                '(%s = :%s AND %s = :%s)',
                $keyColumn,
                $keyIndex,
                $pathColumn,
                $keyPathIndex
            );

            $this->params[$keyIndex] = $keyParam;
            $this->params[$keyPathIndex] = $keyPathParam;
        }

        // Add parent folders to distinct conditions as well
        if (count($pathParts) > 1) {
            $this->addDistinctConditions($pathParts, $keyColumn, $pathColumn);
        }
    }

    /**
     * Runs the database query and returns found ID's.
     *
     * @param string                $from
     * @param string                $select
     *
     * @return array<int, mixed>
     */
    private function fetchIdsFromDatabaseTable(string $from, string $select): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select($select)
            ->from($from)
            ->where(implode(' OR ', $this->conditions[self::CONDITION_INCLUSIVE]))
            ->setParameters($this->params);

        if (isset($this->conditions[self::CONDITION_DISTINCT])) {
            $qb->orWhere(implode(' OR ', $this->conditions[self::CONDITION_DISTINCT]));
        }

        if (isset($this->conditions[self::CONDITION_EXCLUSIVE])) {
            $qb->andWhere(implode(' OR ', $this->conditions[self::CONDITION_EXCLUSIVE]));
        }

        try {
            /** @var Statement $statement */
            $statement = $qb->execute();
            $ids = $statement->fetchFirstColumn();
        } catch (DBALException | DBALDriverException $e) {
            $ids = [];
        }

        return $ids;
    }
}
