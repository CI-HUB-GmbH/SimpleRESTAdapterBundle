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

namespace CIHub\Bundle\SimpleRESTAdapterBundle\Provider;

use Pimcore\Localization\LocaleService;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool;
use Webmozart\Assert\Assert;
use CIHub\Bundle\SimpleRESTAdapterBundle\DataCollector\CompositeDataCollector;
use CIHub\Bundle\SimpleRESTAdapterBundle\Reader\ConfigReader;

final class DataObjectProvider implements ProviderInterface
{
    /**
     * @var CompositeDataCollector
     */
    private $dataCollector;

    /**
     * @param CompositeDataCollector $dataCollector
     */
    public function __construct(CompositeDataCollector $dataCollector)
    {
        $this->dataCollector = $dataCollector;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexData(ElementInterface $element, ConfigReader $reader): array
    {
        /** @var DataObject\AbstractObject $element */
        Assert::isInstanceOf($element, DataObject\AbstractObject::class);

        $data = [
            'system' => $this->getSystemValues($element),
        ];

        if ($element instanceof DataObject\Concrete) {
            $data['data'] = $this->getDataValues($element, $reader);
        }

        return $data;
    }

    /**
     * Returns the data values of an object.
     *
     * @param DataObject\Concrete $object
     * @param ConfigReader        $reader
     *
     * @return array<string, mixed>
     */
    private function getDataValues(DataObject\Concrete $object, ConfigReader $reader): array
    {
        $objectSchema = $reader->extractObjectSchema($object->getClassName());
        $fields = $objectSchema['columnConfig'] ?? [];

        $data = DataObject\Service::getCsvDataForObject(
            $object,
            Tool::getDefaultLanguage(),
            array_keys($fields),
            $fields,
            new LocaleService(),
            true
        );

        // Collect data for special field types, such as images/hotspot images/image galleries
        foreach ($fields as $key => $field) {
            $fieldValue = $this->dataCollector->collect($object, $key, $reader);

            if (null === $fieldValue) {
                continue;
            }

            $data[$key] = $fieldValue;
        }

        return $data;
    }

    /**
     * Returns the system values of an object.
     *
     * @param DataObject\AbstractObject $object
     *
     * @return array<string, mixed>
     */
    private function getSystemValues(DataObject\AbstractObject $object): array
    {
        return [
            'id' => $object->getId(),
            'key' => $object->getKey(),
            'fullPath' => $object->getFullPath(),
            'parentId' => $object->getParentId(),
            'type' => 'object',
            'subtype' => $object->getType(),
            'hasChildren' => $object->hasChildren(),
            'creationDate' => $object->getCreationDate(),
            'modificationDate' => $object->getModificationDate(),
        ];
    }
}
