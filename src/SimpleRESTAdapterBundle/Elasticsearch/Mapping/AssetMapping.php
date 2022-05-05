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
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

final class AssetMapping extends DefaultMapping
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $config = []): array
    {
        if (empty($config)) {
            throw new RuntimeException('No configuration provided.');
        }

        return array_merge($this->getCommonProperties(), [
            'properties' => [
                'binaryData' => [
                    'dynamic' => 'false',
                    'properties' => $this->generateBinaryDataProperties($config),
                ],
                'dimensionData' => [
                    'dynamic' => 'false',
                    'properties' => [
                        'width' => [
                            'type' => 'integer',
                        ],
                        'height' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
                'exifData' => [
                    'dynamic' => 'true',
                    'type' => 'object',
                ],
                'iptcData' => [
                    'dynamic' => 'true',
                    'type' => 'object',
                ],
                'metaData' => [
                    'dynamic' => 'true',
                    'type' => 'object',
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
                        'checksum' => [
                            'type' => 'keyword',
                        ],
                        'mimeType' => [
                            'type' => 'keyword',
                        ],
                        'fileSize' => [
                            'type' => 'long',
                        ],
                    ],
                ],
                'xmpData' => [
                    'dynamic' => 'true',
                    'type' => 'object',
                ],
            ],
        ]);
    }

    /**
     * @param array<string, array> $config
     *
     * @return array<string, array>
     */
    private function generateBinaryDataProperties(array $config): array
    {
        $properties = [];

        $reader = new ConfigReader($config);
        $thumbnails = $reader->getAssetThumbnails();
        $binaryMapping = [
            'dynamic' => 'false',
            'type' => 'object',
            'properties' => $this->getBinaryDataProperties(),
        ];

        if ($reader->isOriginalImageAllowed()) {
            $properties['original'] = $binaryMapping;
        }

        foreach ($thumbnails as $thumbnail) {
            $properties[$thumbnail] = $binaryMapping;
        }

        return $properties;
    }
}

