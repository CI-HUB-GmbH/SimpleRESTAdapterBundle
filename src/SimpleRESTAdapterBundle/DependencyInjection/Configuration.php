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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('simple_rest_adapter');
        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('index_name_prefix')
                    ->info('Prefix for index names.')
                    ->defaultValue('datahub_restindex')
                    ->validate()
                        ->ifString()
                        ->then(static function ($value) {
                            return rtrim(str_replace('-', '_', $value), '_');
                        })
                    ->end()
                ->end()
                ->arrayNode('es_hosts')
                    ->info('List of Elasticsearch hosts.')
                    ->prototype('scalar')->end()
                    ->defaultValue(['localhost'])
                ->end()
                ->variableNode('index_settings')
                    ->info('Global Elasticsearch index settings.')
                    ->defaultValue([
                        'number_of_shards' => 5,
                        'number_of_replicas' => 0,
                        'max_ngram_diff' => 20,
                        'analysis' => [
                            'analyzer' => [
                                'datahub_ngram_analyzer' => [
                                    'type' => 'custom',
                                    'tokenizer' => 'datahub_ngram_tokenizer',
                                    'filter' => ['lowercase'],
                                ],
                                'datahub_whitespace_analyzer' => [
                                    'type' => 'custom',
                                    'tokenizer' => 'datahub_whitespace_tokenizer',
                                    'filter' => ['lowercase'],
                                ],
                            ],
                            'normalizer' => [
                                'lowercase' => [
                                    'type' => 'custom',
                                    'filter' => ['lowercase'],
                                ],
                            ],
                            'tokenizer' => [
                                'datahub_ngram_tokenizer' => [
                                    'type' => 'nGram',
                                    'min_gram' => 2,
                                    'max_gram' => 20,
                                    'token_chars' => ['letter', 'digit'],
                                ],
                                'datahub_whitespace_tokenizer' => [
                                    'type' => 'whitespace',
                                ],
                            ],
                        ],
                    ])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
