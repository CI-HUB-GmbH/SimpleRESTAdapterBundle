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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Extractor;

use CIHub\Bundle\SimpleRESTAdapterBundle\Manager\IndexManager;

final class LabelExtractor implements LabelExtractorInterface
{
    public const ALLOWED_PROPERTIES = ['data', 'dimensionData', 'metaData', 'system'];
    public const ALLOWED_SYSTEM_PROPERTIES = ['id', 'key', 'mimeType', 'subtype', 'type'];

    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @param IndexManager $indexManager
     */
    public function __construct(IndexManager $indexManager)
    {
        $this->indexManager = $indexManager;
    }

    /**
     * {@inheritdoc}
     */
    public function extractLabels(array $indices): array
    {
        $labels = [];

        foreach ($indices as $index) {
            $mapping = $this->indexManager->getIndexMapping($index);

            if (empty($mapping)) {
                continue;
            }

            foreach ($mapping['properties'] as $property => $definition) {
                if (!in_array($property, self::ALLOWED_PROPERTIES, true)) {
                    continue;
                }

                $labels[] = array_map(
                    static function ($item) use ($property) {
                        return sprintf('%s.%s', $property, $item);
                    },
                    array_filter(
                        array_keys($definition['properties'] ?? []),
                        static function ($key) use ($property) {
                            return 'system' !== $property ||
                                in_array($key, self::ALLOWED_SYSTEM_PROPERTIES, true);
                        }
                    )
                );
            }
        }

        return array_values(array_unique(array_merge([], ...$labels)));
    }
}
