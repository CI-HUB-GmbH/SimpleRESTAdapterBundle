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

final class FolderMapping extends DefaultMapping
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $config = []): array
    {
        return array_merge($this->getCommonProperties(), [
            'properties' => [
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
                            'type' => 'constant_keyword',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
