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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\DataCollector;

use Pimcore\Model\DataObject\Concrete;
use Webmozart\Assert\Assert;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

final class CompositeDataCollector
{
    /**
     * @var iterable<DataCollectorInterface>
     */
    private $collectors;

    /**
     * @param iterable<DataCollectorInterface> $collectors
     */
    public function __construct(iterable $collectors)
    {
        $this->collectors = $collectors;
    }

    /**
     * Loops through all data collectors to find one, that supports the provided value.
     * If the value if supported, the data collector does its thing and returns the serialized data.
     *
     * @param Concrete     $concrete
     * @param string       $fieldName
     * @param ConfigReader $reader
     *
     * @return array<int|string, mixed>|null
     */
    public function collect(Concrete $concrete, string $fieldName, ConfigReader $reader): ?array
    {
        $value = $concrete->getValueForFieldName($fieldName);

        foreach ($this->collectors as $collector) {
            Assert::isInstanceOf($collector, DataCollectorInterface::class);

            if (!$collector->supports($value)) {
                continue;
            }

            return $collector->collect($value, $reader);
        }

        return null;
    }
}
