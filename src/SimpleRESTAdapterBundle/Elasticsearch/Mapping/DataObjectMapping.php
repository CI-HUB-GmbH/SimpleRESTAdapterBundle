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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Mapping;

use RuntimeException;

final class DataObjectMapping extends DefaultMapping
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $config = []): array
    {
        if (empty($config)) {
            throw new RuntimeException('No DataObject class configuration provided.');
        }

        return array_merge($this->getCommonProperties(), [
            'properties' => [
                'data' => [
                    'dynamic' => 'true',
                    'properties' => $this->generateDataProperties($config),
                ],
                'system' => [
                    'dynamic' => 'false',
                    'properties' => [
                        'id' => [
                            'type' => 'long',
                        ],
                        'key' => [
                            'type' => 'keyword',
                            'fields' => [
                                'analyzed' => [
                                    'type' => 'text',
                                    'term_vector' => 'yes',
                                    'analyzer' => 'datahub_ngram_analyzer',
                                    'search_analyzer' => 'datahub_whitespace_analyzer',
                                ],
                            ],
                        ],
                        'fullPath' => [
                            'type' => 'keyword',
                            'fields' => [
                                'analyzed' => [
                                    'type' => 'text',
                                    'term_vector' => 'yes',
                                    'analyzer' => 'datahub_ngram_analyzer',
                                    'search_analyzer' => 'datahub_whitespace_analyzer',
                                ],
                            ],
                        ],
                        'type' => [
                            'type' => 'constant_keyword',
                        ],
                        'parentId' => [
                            'type' => 'keyword',
                        ],
                        'hasChildren' => [
                            'type' => 'boolean',
                        ],
                        'creationDate' => [
                            'type' => 'date',
                        ],
                        'modificationDate' => [
                            'type' => 'date',
                        ],
                        'subtype' => [
                            'type' => 'keyword',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Generates all data properties for the given DataObject class config.
     *
     * @param array<string, array|string> $config
     *
     * @return array<string, array>
     */
    private function generateDataProperties(array $config): array
    {
        $properties = [];
        $columnConfig = $config['columnConfig'] ?? [];

        foreach ($columnConfig as $column) {
            if (true === $column['hidden']) {
                continue;
            }

            $properties[$column['name']] = $this->getPropertiesForFieldConfig($column['fieldConfig']);
        }

        return $properties;
    }

    /**
     * Generates the property definition for a given field config.
     *
     * @param array<string, array|string> $config
     *
     * @return array<string, array|string>
     */
    private function getPropertiesForFieldConfig(array $config): array
    {
        switch ($config['type']) {
            case 'hotspotimage':
            case 'image':
                $mapping = array_merge($this->getImageProperties(), [
                    'dynamic' => 'false',
                    'type' => 'object',
                ]);

                break;
            case 'imageGallery':
                $mapping = array_merge($this->getImageProperties(), [
                    'dynamic' => 'false',
                    'type' => 'nested',
                ]);

                break;
            case 'numeric':
                $mapping = [
                    'type' => $config['layout']['integer'] ? 'integer' : 'float',
                ];

                break;
            default:
                $mapping = [
                    'type' => 'keyword',
                    'fields' => [
                        'analyzed' => [
                            'type' => 'text',
                            'term_vector' => 'yes',
                            'analyzer' => 'datahub_ngram_analyzer',
                            'search_analyzer' => 'datahub_whitespace_analyzer',
                        ],
                    ],
                ];
        }

        return $mapping;
    }
}
