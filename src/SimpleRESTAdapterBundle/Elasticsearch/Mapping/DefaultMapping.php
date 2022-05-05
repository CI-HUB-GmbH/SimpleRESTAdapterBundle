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

abstract class DefaultMapping implements MappingInterface
{
    /**
     * @return array<string, array>
     */
    public function getBinaryDataProperties(): array
    {
        return [
            'checksum' => [
                'type' => 'keyword',
            ],
            'path' => [
                'type' => 'keyword',
            ],
            'filename' => [
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
        ];
    }

    /**
     * @return array<string, array|bool>
     */
    public function getCommonProperties(): array
    {
        return [
            'dynamic_templates' => [
                [
                    'strings' => [
                        'match_mapping_type' => 'string',
                        'mapping' => [
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
                    ],
                ],
            ],
            'numeric_detection' => false,
        ];
    }

    /**
     * @return array<string, array>
     */
    public function getImageProperties(): array
    {
        return [
            'properties' => [
                'id' => [
                    'type' => 'long',
                ],
                'type' => [
                    'type' => 'constant_keyword',
                ],
                'binaryData' => [
                    'dynamic' => 'false',
                    'type' => 'object',
                    'properties' => $this->getBinaryDataProperties(),
                ],
            ],
        ];
    }
}
