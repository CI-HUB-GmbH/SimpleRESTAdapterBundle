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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Elasticsearch\Builder;

use Elasticsearch\Client;

final class ClientBuilder implements ClientBuilderInterface
{
    /**
     * @var array<int, string>
     */
    private $hosts;

    /**
     * @param array<int, string> $hosts
     */
    public function __construct(array $hosts)
    {
        $this->hosts = $hosts;
    }

    /**
     * {@inheritdoc}
     */
    public function build(): Client
    {
        $client = \Elasticsearch\ClientBuilder::create();
        $client->setHosts($this->hosts);

        return $client->build();
    }
}
